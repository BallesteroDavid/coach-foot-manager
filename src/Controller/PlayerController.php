<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Player;
use App\Entity\Team;
use App\Form\PlayerType;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/player')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PlayerController extends AbstractController
{
    #[Route(name: 'app_player_index', methods: ['GET'])]
    public function index(
        PlayerRepository $playerRepository,
        TeamRepository $teamRepository
    ): Response {
        $currentUser = $this->getCurrentAppUser();

        $players = $playerRepository->findVisibleForUser($currentUser);
        $manageablePlayers = $playerRepository->findManageableForUser($currentUser);
        $manageableTeams = $teamRepository->findManageableForUser($currentUser);

        $manageablePlayerIds = array_map(
            static fn (Player $player): int => $player->getId(),
            $manageablePlayers
        );

        return $this->render('player/index.html.twig', [
            'players' => $players,
            'manageable_player_ids' => $manageablePlayerIds,
            'can_create_player' => count($manageableTeams) > 0,
        ]);
    }

    #[Route('/new', name: 'app_player_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        TeamRepository $teamRepository
    ): Response {
        $currentUser = $this->getCurrentAppUser();

        $manageableTeams = $teamRepository->findManageableForUser($currentUser);

        if (count($manageableTeams) === 0) {
            throw $this->createAccessDeniedException('Vous ne pouvez créer un joueur dans aucune équipe.');
        }

        $player = new Player();

        $form = $this->createForm(PlayerType::class, $player, [
            'available_teams' => $manageableTeams,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team = $player->getTeam();

            if ($team === null || !$this->canManageTeam($team)) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas créer un joueur dans cette équipe.');
            }

            $entityManager->persist($player);
            $entityManager->flush();

            $this->addFlash('success', 'Le joueur a bien été créé.');

            return $this->redirectToRoute('app_player_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('player/new.html.twig', [
            'player' => $player,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_player_show', methods: ['GET'])]
    public function show(Player $player): Response
    {
        if (!$this->canViewPlayer($player)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à ce joueur.');
        }

        return $this->render('player/show.html.twig', [
            'player' => $player,
            'can_manage' => $this->canManagePlayer($player),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_player_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Player $player,
        EntityManagerInterface $entityManager,
        TeamRepository $teamRepository
    ): Response {
        if (!$this->canManagePlayer($player)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier ce joueur.');
        }

        $currentUser = $this->getCurrentAppUser();

        $manageableTeams = $teamRepository->findManageableForUser($currentUser);

        if (count($manageableTeams) === 0) {
            throw $this->createAccessDeniedException('Vous ne pouvez modifier aucun joueur.');
        }

        $form = $this->createForm(PlayerType::class, $player, [
            'available_teams' => $manageableTeams,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team = $player->getTeam();

            if ($team === null || !$this->canManageTeam($team)) {
                throw $this->createAccessDeniedException('Vous ne pouvez pas affecter ce joueur à cette équipe.');
            }

            $player->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Le joueur a bien été modifié.');

            return $this->redirectToRoute('app_player_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('player/edit.html.twig', [
            'player' => $player,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_player_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Player $player,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canManagePlayer($player)) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer ce joueur.');
        }

        if ($this->isCsrfTokenValid('delete'.$player->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($player);
            $entityManager->flush();

            $this->addFlash('success', 'Le joueur a bien été supprimé.');
        }

        return $this->redirectToRoute('app_player_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canViewPlayer(Player $player): bool
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            return false;
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        $team = $player->getTeam();

        if ($team === null) {
            return false;
        }

        $userClub = $currentUser->getClub();
        $teamClub = $team->getClub();

        if ($userClub === null || $teamClub === null) {
            return false;
        }

        if (
            $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_ADMIN_CLUB')
            || $this->isGranted('ROLE_COACH')
        ) {
            return $userClub->getId() === $teamClub->getId();
        }

        return false;
    }

    private function canManagePlayer(Player $player): bool
    {
        $team = $player->getTeam();

        if ($team === null) {
            return false;
        }

        return $this->canManageTeam($team);
    }

    private function canManageTeam(Team $team): bool
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            return false;
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_CLUB')) {
            $userClub = $currentUser->getClub();
            $teamClub = $team->getClub();

            return $userClub !== null
                && $teamClub !== null
                && $userClub->getId() === $teamClub->getId();
        }

        if ($this->isGranted('ROLE_COACH')) {
            foreach ($currentUser->getCoachedTeams() as $coachedTeam) {
                if ($coachedTeam->getId() === $team->getId()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getCurrentAppUser(): AppUser
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        return $currentUser;
    }
}
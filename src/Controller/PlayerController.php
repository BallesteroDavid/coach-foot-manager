<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Player;
use App\Form\PlayerType;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/player')]
#[IsGranted('ROLE_COACH')]
final class PlayerController extends AbstractController
{
    #[Route(name: 'app_player_index', methods: ['GET'])]
    public function index(PlayerRepository $playerRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('player/index.html.twig', [
            'players' => $playerRepository->findVisibleForUser($currentUser),
        ]);
    }

    #[Route('/new', name: 'app_player_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $player = new Player();
        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        if (!$this->canAccessPlayer($player)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas accéder à ce joueur.");
        }

        return $this->render('player/show.html.twig', [
            'player' => $player,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_player_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Player $player, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canAccessPlayer($player)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier ce joueur.");
        }

        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Player $player, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canAccessPlayer($player)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer ce joueur.");
        }

        if ($this->isCsrfTokenValid('delete'.$player->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($player);
            $entityManager->flush();

            $this->addFlash('success', 'Le joueur a bien été supprimé.');
        }

        return $this->redirectToRoute('app_player_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canAccessPlayer(Player $player): bool
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

        if ($this->isGranted('ROLE_ADMIN')) {
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
}
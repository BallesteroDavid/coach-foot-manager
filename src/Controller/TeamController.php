<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Team;
use App\Form\TeamType;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/team')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class TeamController extends AbstractController
{
    #[Route(name: 'app_team_index', methods: ['GET'])]
    public function index(TeamRepository $teamRepository): Response
    {
        $currentUser = $this->getCurrentAppUser();

        $teams = $teamRepository->findVisibleForUser($currentUser);
        $manageableTeams = $teamRepository->findManageableForUser($currentUser);

        $manageableTeamIds = array_map(
            static fn (Team $team): int => $team->getId(),
            $manageableTeams
        );

        return $this->render('team/index.html.twig', [
            'teams' => $teams,
            'manageable_team_ids' => $manageableTeamIds,
            'can_create_team' => $this->canCreateTeam(),
        ]);
    }

    #[Route('/new', name: 'app_team_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canCreateTeam()) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas créer d'équipe.");
        }

        $currentUser = $this->getCurrentAppUser();

        $team = new Team();

        $form = $this->createForm(TeamType::class, $team, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canCreateTeamForClub($team)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas créer une équipe pour ce club.");
            }

            $entityManager->persist($team);
            $entityManager->flush();

            $this->addFlash('success', "L'équipe a bien été créée.");

            return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('team/new.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_team_show', methods: ['GET'])]
    public function show(Team $team): Response
    {
        if (!$this->canViewTeam($team)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas accéder à cette équipe.");
        }

        return $this->render('team/show.html.twig', [
            'team' => $team,
            'can_manage' => $this->canManageTeam($team),
            'can_delete' => $this->canDeleteTeam($team),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_team_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Team $team,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canManageTeam($team)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier cette équipe.");
        }

        $currentUser = $this->getCurrentAppUser();

        $form = $this->createForm(TeamType::class, $team, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canManageTeam($team)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas rattacher cette équipe à ce club.");
            }

            $team->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', "L'équipe a bien été modifiée.");

            return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('team/edit.html.twig', [
            'team' => $team,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_team_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Team $team,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canDeleteTeam($team)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer cette équipe.");
        }

        if ($this->isCsrfTokenValid('delete'.$team->getId(), $request->getPayload()->getString('_token'))) {
            if (
                $team->getPlayers()->count() > 0
                || $team->getFootballMatches()->count() > 0
                || $team->getTrainingSessions()->count() > 0
            ) {
                $this->addFlash(
                    'danger',
                    "Impossible de supprimer cette équipe : elle possède déjà des joueurs, des matchs ou des entraînements associés."
                );

                return $this->redirectToRoute('app_team_show', [
                    'id' => $team->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($team);
            $entityManager->flush();

            $this->addFlash('success', "L'équipe a bien été supprimée.");
        }

        return $this->redirectToRoute('app_team_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canViewTeam(Team $team): bool
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            return false;
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
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
            if ($team->getId() === null) {
                return false;
            }

            foreach ($currentUser->getCoachedTeams() as $coachedTeam) {
                if ($coachedTeam->getId() === $team->getId()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function canCreateTeam(): bool
    {
        return $this->isGranted('ROLE_SUPER_ADMIN')
            || $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_ADMIN_CLUB');
    }

    private function canCreateTeamForClub(Team $team): bool
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

        return false;
    }

    private function canDeleteTeam(Team $team): bool
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
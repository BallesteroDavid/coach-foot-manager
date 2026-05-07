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
#[IsGranted('ROLE_COACH')]
final class TeamController extends AbstractController
{
    #[Route(name: 'app_team_index', methods: ['GET'])]
    public function index(TeamRepository $teamRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('team/index.html.twig', [
            'teams' => $teamRepository->findVisibleForUser($currentUser),
        ]);
    }

    #[Route('/new', name: 'app_team_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $team = new Team();

        $form = $this->createForm(TeamType::class, $team, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessTeam($team)) {
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
        if (!$this->canAccessTeam($team)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas accéder à cette équipe.");
        }

        return $this->render('team/show.html.twig', [
            'team' => $team,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_team_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        Team $team,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessTeam($team)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier cette équipe.");
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TeamType::class, $team, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessTeam($team)) {
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
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        Team $team,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessTeam($team)) {
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

    private function canAccessTeam(Team $team): bool
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            return false;
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
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
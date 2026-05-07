<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Team;
use App\Entity\TrainingSession;
use App\Form\TrainingSessionType;
use App\Repository\TrainingSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/training/session')]
#[IsGranted('ROLE_COACH')]
final class TrainingSessionController extends AbstractController
{
    #[Route(name: 'app_training_session_index', methods: ['GET'])]
    public function index(TrainingSessionRepository $trainingSessionRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('training_session/index.html.twig', [
            'training_sessions' => $trainingSessionRepository->findVisibleForUser($currentUser),
        ]);
    }

    #[Route('/new', name: 'app_training_session_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $trainingSession = new TrainingSession();
        $trainingSession->setCreatedBy($currentUser);

        $form = $this->createForm(TrainingSessionType::class, $trainingSession, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessTrainingSession($trainingSession)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas créer un entraînement pour cette équipe.");
            }

            $entityManager->persist($trainingSession);
            $entityManager->flush();

            $this->addFlash('success', "L'entraînement a bien été créé.");

            return $this->redirectToRoute('app_training_session_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('training_session/new.html.twig', [
            'training_session' => $trainingSession,
            'form' => $form,
        ]);
    }

    #[Route('/team/{id}/new', name: 'app_training_session_new_from_team', methods: ['GET', 'POST'])]
    public function newFromTeam(
        Request $request,
        Team $team,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessTeam($team)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas créer un entraînement pour cette équipe.");
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $trainingSession = new TrainingSession();
        $trainingSession->setTeam($team);
        $trainingSession->setCreatedBy($currentUser);

        $form = $this->createForm(TrainingSessionType::class, $trainingSession, [
            'hide_team' => true,
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessTrainingSession($trainingSession)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas créer cet entraînement.");
            }

            $entityManager->persist($trainingSession);
            $entityManager->flush();

            $this->addFlash('success', "L'entraînement a bien été créé.");

            return $this->redirectToRoute('app_team_show', [
                'id' => $team->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('training_session/new.html.twig', [
            'training_session' => $trainingSession,
            'form' => $form,
            'team' => $team,
        ]);
    }

    #[Route('/{id}', name: 'app_training_session_show', methods: ['GET'])]
    public function show(TrainingSession $trainingSession): Response
    {
        if (!$this->canAccessTrainingSession($trainingSession)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas accéder à cet entraînement.");
        }

        return $this->render('training_session/show.html.twig', [
            'training_session' => $trainingSession,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_training_session_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        TrainingSession $trainingSession,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessTrainingSession($trainingSession)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier cet entraînement.");
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TrainingSessionType::class, $trainingSession, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessTrainingSession($trainingSession)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas rattacher cet entraînement à cette équipe.");
            }

            $trainingSession->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', "L'entraînement a bien été modifié.");

            return $this->redirectToRoute('app_training_session_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('training_session/edit.html.twig', [
            'training_session' => $trainingSession,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_training_session_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        TrainingSession $trainingSession,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessTrainingSession($trainingSession)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer cet entraînement.");
        }

        $team = $trainingSession->getTeam();

        if ($this->isCsrfTokenValid('delete'.$trainingSession->getId(), $request->getPayload()->getString('_token'))) {
            if ($trainingSession->getAttendances()->count() > 0) {
                $this->addFlash(
                    'danger',
                    "Impossible de supprimer cet entraînement : des présences sont déjà renseignées."
                );

                return $this->redirectToRoute('app_training_session_show', [
                    'id' => $trainingSession->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($trainingSession);
            $entityManager->flush();

            $this->addFlash('success', "L'entraînement a bien été supprimé.");
        }

        if ($team !== null) {
            return $this->redirectToRoute('app_team_show', [
                'id' => $team->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_training_session_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canAccessTrainingSession(TrainingSession $trainingSession): bool
    {
        $team = $trainingSession->getTeam();

        if ($team === null) {
            return false;
        }

        return $this->canAccessTeam($team);
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
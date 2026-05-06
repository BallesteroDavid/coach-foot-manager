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

#[Route('/training/session')]
final class TrainingSessionController extends AbstractController
{
    #[Route(name: 'app_training_session_index', methods: ['GET'])]
    public function index(TrainingSessionRepository $trainingSessionRepository): Response
    {
        return $this->render('training_session/index.html.twig', [
            'training_sessions' => $trainingSessionRepository->findBy([], [
                'trainingDate' => 'DESC',
                'startTime' => 'ASC',
            ]),
        ]);
    }

    #[Route('/new', name: 'app_training_session_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $trainingSession = new TrainingSession();

        $currentUser = $this->getUser();
        if ($currentUser instanceof AppUser) {
            $trainingSession->setCreatedBy($currentUser);
        }

        $form = $this->createForm(TrainingSessionType::class, $trainingSession);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        $trainingSession = new TrainingSession();
        $trainingSession->setTeam($team);

        $currentUser = $this->getUser();
        if ($currentUser instanceof AppUser) {
            $trainingSession->setCreatedBy($currentUser);
        }

        $form = $this->createForm(TrainingSessionType::class, $trainingSession, [
            'hide_team' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        $form = $this->createForm(TrainingSessionType::class, $trainingSession);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        $team = $trainingSession->getTeam();

        if ($this->isCsrfTokenValid('delete'.$trainingSession->getId(), $request->getPayload()->getString('_token'))) {
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
}
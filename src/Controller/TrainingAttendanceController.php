<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\TrainingAttendance;
use App\Entity\TrainingSession;
use App\Form\TrainingAttendanceType;
use App\Repository\TrainingAttendanceRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/training/attendance')]
final class TrainingAttendanceController extends AbstractController
{
    #[Route(name: 'app_training_attendance_index', methods: ['GET'])]
    public function index(TrainingAttendanceRepository $trainingAttendanceRepository): Response
    {
        return $this->render('training_attendance/index.html.twig', [
            'training_attendances' => $trainingAttendanceRepository->findBy([], [
                'createdAt' => 'DESC',
            ]),
        ]);
    }

    #[Route('/new', name: 'app_training_attendance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $trainingAttendance = new TrainingAttendance();

        $currentUser = $this->getUser();
        if ($currentUser instanceof AppUser) {
            $trainingAttendance->setUpdatedBy($currentUser);
        }

        $form = $this->createForm(TrainingAttendanceType::class, $trainingAttendance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($trainingAttendance);
                $entityManager->flush();

                $this->addFlash('success', 'La présence a bien été créée.');

                return $this->redirectToRoute('app_training_attendance_index', [], Response::HTTP_SEE_OTHER);
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('danger', 'Ce joueur a déjà une présence enregistrée pour cet entraînement.');
            }
        }

        return $this->render('training_attendance/new.html.twig', [
            'training_attendance' => $trainingAttendance,
            'form' => $form,
        ]);
    }

    #[Route('/session/{id}/new', name: 'app_training_attendance_new_from_session', methods: ['GET', 'POST'])]
    public function newFromSession(
        Request $request,
        TrainingSession $trainingSession,
        EntityManagerInterface $entityManager
    ): Response {
        $trainingAttendance = new TrainingAttendance();
        $trainingAttendance->setTrainingSession($trainingSession);

        $currentUser = $this->getUser();
        if ($currentUser instanceof AppUser) {
            $trainingAttendance->setUpdatedBy($currentUser);
        }

        $form = $this->createForm(TrainingAttendanceType::class, $trainingAttendance, [
            'hide_training_session' => true,
            'training_session' => $trainingSession,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($trainingAttendance);
                $entityManager->flush();

                $this->addFlash('success', 'La présence a bien été créée.');

                return $this->redirectToRoute('app_training_session_show', [
                    'id' => $trainingSession->getId(),
                ], Response::HTTP_SEE_OTHER);
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('danger', 'Ce joueur a déjà une présence enregistrée pour cet entraînement.');
            }
        }

        return $this->render('training_attendance/new.html.twig', [
            'training_attendance' => $trainingAttendance,
            'form' => $form,
            'training_session' => $trainingSession,
        ]);
    }

    #[Route('/session/{id}/mark-all-present', name: 'app_training_attendance_mark_all_present', methods: ['POST'])]
    public function markAllPresent(
        Request $request,
        TrainingSession $trainingSession,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('mark_all_present'.$trainingSession->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_training_session_show', [
                'id' => $trainingSession->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $team = $trainingSession->getTeam();

        if ($team === null) {
            $this->addFlash('danger', "Impossible de créer les présences : aucune équipe associée à l'entraînement.");

            return $this->redirectToRoute('app_training_session_show', [
                'id' => $trainingSession->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $currentUser = $this->getUser();
        $updatedBy = $currentUser instanceof AppUser ? $currentUser : null;

        $createdCount = 0;

        foreach ($team->getPlayers() as $player) {
            $alreadyRegistered = false;

            foreach ($trainingSession->getAttendances() as $existingAttendance) {
                if ($existingAttendance->getPlayer() === $player) {
                    $alreadyRegistered = true;
                    break;
                }
            }

            if ($alreadyRegistered) {
                continue;
            }

            $attendance = new TrainingAttendance();
            $attendance->setTrainingSession($trainingSession);
            $attendance->setPlayer($player);
            $attendance->setStatus('present');
            $attendance->setUpdatedBy($updatedBy);

            $entityManager->persist($attendance);
            $createdCount++;
        }

        $entityManager->flush();

        if ($createdCount > 0) {
            $this->addFlash('success', $createdCount.' présence(s) créée(s).');
        } else {
            $this->addFlash('warning', 'Aucune présence créée : tous les joueurs ont déjà une présence pour cet entraînement.');
        }

        return $this->redirectToRoute('app_training_session_show', [
            'id' => $trainingSession->getId(),
        ], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_training_attendance_show', methods: ['GET'])]
    public function show(TrainingAttendance $trainingAttendance): Response
    {
        return $this->render('training_attendance/show.html.twig', [
            'training_attendance' => $trainingAttendance,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_training_attendance_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        TrainingAttendance $trainingAttendance,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(TrainingAttendanceType::class, $trainingAttendance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $trainingAttendance->setUpdatedAt(new \DateTimeImmutable());

            $currentUser = $this->getUser();
            if ($currentUser instanceof AppUser) {
                $trainingAttendance->setUpdatedBy($currentUser);
            }

            try {
                $entityManager->flush();

                $this->addFlash('success', 'La présence a bien été modifiée.');

                return $this->redirectToRoute('app_training_attendance_index', [], Response::HTTP_SEE_OTHER);
            } catch (UniqueConstraintViolationException) {
                $this->addFlash('danger', 'Ce joueur a déjà une présence enregistrée pour cet entraînement.');
            }
        }

        return $this->render('training_attendance/edit.html.twig', [
            'training_attendance' => $trainingAttendance,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_training_attendance_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        TrainingAttendance $trainingAttendance,
        EntityManagerInterface $entityManager
    ): Response {
        $trainingSession = $trainingAttendance->getTrainingSession();

        if ($this->isCsrfTokenValid('delete'.$trainingAttendance->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($trainingAttendance);
            $entityManager->flush();

            $this->addFlash('success', 'La présence a bien été supprimée.');
        }

        if ($trainingSession !== null) {
            return $this->redirectToRoute('app_training_session_show', [
                'id' => $trainingSession->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('app_training_attendance_index', [], Response::HTTP_SEE_OTHER);
    }
}
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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/training/attendance')]
#[IsGranted('ROLE_COACH')]
final class TrainingAttendanceController extends AbstractController
{
    #[Route(name: 'app_training_attendance_index', methods: ['GET'])]
    public function index(TrainingAttendanceRepository $trainingAttendanceRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('training_attendance/index.html.twig', [
            'training_attendances' => $trainingAttendanceRepository->findVisibleForUser($currentUser),
        ]);
    }

    #[Route('/new', name: 'app_training_attendance_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $trainingAttendance = new TrainingAttendance();
        $trainingAttendance->setUpdatedBy($currentUser);

        $form = $this->createForm(TrainingAttendanceType::class, $trainingAttendance, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessTrainingAttendance($trainingAttendance)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas créer cette présence.");
            }

            if (!$this->isPlayerInTrainingSessionTeam($trainingAttendance)) {
                $this->addFlash(
                    'danger',
                    "Le joueur sélectionné n'appartient pas à l'équipe de cet entraînement."
                );

                return $this->render('training_attendance/new.html.twig', [
                    'training_attendance' => $trainingAttendance,
                    'form' => $form,
                ]);
            }

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
        if (!$this->canAccessTrainingSession($trainingSession)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas créer une présence pour cet entraînement.");
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $trainingAttendance = new TrainingAttendance();
        $trainingAttendance->setTrainingSession($trainingSession);
        $trainingAttendance->setUpdatedBy($currentUser);

        $form = $this->createForm(TrainingAttendanceType::class, $trainingAttendance, [
            'hide_training_session' => true,
            'training_session' => $trainingSession,
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessTrainingAttendance($trainingAttendance)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas créer cette présence.");
            }

            if (!$this->isPlayerInTrainingSessionTeam($trainingAttendance)) {
                $this->addFlash(
                    'danger',
                    "Le joueur sélectionné n'appartient pas à l'équipe de cet entraînement."
                );

                return $this->render('training_attendance/new.html.twig', [
                    'training_attendance' => $trainingAttendance,
                    'form' => $form,
                    'training_session' => $trainingSession,
                ]);
            }

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
        if (!$this->canAccessTrainingSession($trainingSession)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas gérer les présences de cet entraînement.");
        }

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

    #[Route('/session/{id}/manage', name: 'app_training_attendance_manage_session', methods: ['GET', 'POST'])]
    public function manageSessionAttendances(
        Request $request,
        TrainingSession $trainingSession,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessTrainingSession($trainingSession)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas gérer la feuille de présence de cet entraînement.");
        }

        $team = $trainingSession->getTeam();

        if ($team === null) {
            $this->addFlash('danger', "Impossible de gérer les présences : aucune équipe associée à l'entraînement.");

            return $this->redirectToRoute('app_training_session_show', [
                'id' => $trainingSession->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $existingAttendances = [];

        foreach ($trainingSession->getAttendances() as $attendance) {
            $player = $attendance->getPlayer();

            if ($player !== null) {
                $existingAttendances[$player->getId()] = $attendance;
            }
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('manage_attendances'.$trainingSession->getId(), $request->getPayload()->getString('_token'))) {
                $this->addFlash('danger', 'Token de sécurité invalide.');

                return $this->redirectToRoute('app_training_attendance_manage_session', [
                    'id' => $trainingSession->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            $statuses = $request->request->all('statuses');
            $comments = $request->request->all('comments');

            $allowedStatuses = [
                'present',
                'absent',
                'excused',
                'late',
                'injured',
                'exempt',
            ];

            $currentUser = $this->getUser();
            $updatedBy = $currentUser instanceof AppUser ? $currentUser : null;

            foreach ($team->getPlayers() as $player) {
                $playerId = $player->getId();

                if ($playerId === null) {
                    continue;
                }

                $status = $statuses[$playerId] ?? 'present';

                if (!in_array($status, $allowedStatuses, true)) {
                    $status = 'present';
                }

                $comment = trim($comments[$playerId] ?? '');
                $comment = $comment !== '' ? $comment : null;

                $attendance = $existingAttendances[$playerId] ?? null;

                if (!$attendance instanceof TrainingAttendance) {
                    $attendance = new TrainingAttendance();
                    $attendance->setTrainingSession($trainingSession);
                    $attendance->setPlayer($player);

                    $entityManager->persist($attendance);
                } else {
                    $attendance->setUpdatedAt(new \DateTimeImmutable());
                }

                $attendance->setStatus($status);
                $attendance->setComment($comment);
                $attendance->setUpdatedBy($updatedBy);
            }

            $entityManager->flush();

            $this->addFlash('success', 'La feuille de présence a bien été enregistrée.');

            return $this->redirectToRoute('app_training_session_show', [
                'id' => $trainingSession->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('training_attendance/manage_session.html.twig', [
            'training_session' => $trainingSession,
            'team' => $team,
            'existing_attendances' => $existingAttendances,
        ]);
    }

    #[Route('/{id}', name: 'app_training_attendance_show', methods: ['GET'])]
    public function show(TrainingAttendance $trainingAttendance): Response
    {
        if (!$this->canAccessTrainingAttendance($trainingAttendance)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas accéder à cette présence.");
        }

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
        if (!$this->canAccessTrainingAttendance($trainingAttendance)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier cette présence.");
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $trainingSession = $trainingAttendance->getTrainingSession();

        $form = $this->createForm(TrainingAttendanceType::class, $trainingAttendance, [
            'hide_training_session' => true,
            'training_session' => $trainingSession,
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessTrainingAttendance($trainingAttendance)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas modifier cette présence.");
            }

            if (!$this->isPlayerInTrainingSessionTeam($trainingAttendance)) {
                $this->addFlash(
                    'danger',
                    "Le joueur sélectionné n'appartient pas à l'équipe de cet entraînement."
                );

                return $this->render('training_attendance/edit.html.twig', [
                    'training_attendance' => $trainingAttendance,
                    'form' => $form,
                ]);
            }

            $trainingAttendance->setUpdatedAt(new \DateTimeImmutable());
            $trainingAttendance->setUpdatedBy($currentUser);

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
        if (!$this->canAccessTrainingAttendance($trainingAttendance)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer cette présence.");
        }

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

    private function canAccessTrainingAttendance(TrainingAttendance $trainingAttendance): bool
    {
        $trainingSession = $trainingAttendance->getTrainingSession();

        if ($trainingSession === null) {
            return false;
        }

        return $this->canAccessTrainingSession($trainingSession);
    }

    private function canAccessTrainingSession(TrainingSession $trainingSession): bool
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            return false;
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        $team = $trainingSession->getTeam();

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

    private function isPlayerInTrainingSessionTeam(TrainingAttendance $trainingAttendance): bool
    {
        $trainingSession = $trainingAttendance->getTrainingSession();
        $player = $trainingAttendance->getPlayer();

        if ($trainingSession === null || $player === null) {
            return false;
        }

        $trainingTeam = $trainingSession->getTeam();
        $playerTeam = $player->getTeam();

        if ($trainingTeam === null || $playerTeam === null) {
            return false;
        }

        return $trainingTeam->getId() === $playerTeam->getId();
    }
}
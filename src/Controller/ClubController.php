<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Club;
use App\Form\ClubType;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/club')]
#[IsGranted('ROLE_ADMIN')]
final class ClubController extends AbstractController
{
    #[Route(name: 'app_club_index', methods: ['GET'])]
    public function index(ClubRepository $clubRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('club/index.html.twig', [
            'clubs' => $clubRepository->findVisibleForUser($currentUser),
        ]);
    }

    #[Route('/new', name: 'app_club_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $club = new Club();

        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($club);
            $entityManager->flush();

            $this->addFlash('success', 'Le club a bien été créé.');

            return $this->redirectToRoute('app_club_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('club/new.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_club_show', methods: ['GET'])]
    public function show(Club $club): Response
    {
        if (!$this->canAccessClub($club)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas accéder à ce club.");
        }

        return $this->render('club/show.html.twig', [
            'club' => $club,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_club_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Club $club,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessClub($club)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier ce club.");
        }

        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessClub($club)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas modifier ce club.");
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le club a bien été modifié.');

            return $this->redirectToRoute('app_club_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('club/edit.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_club_delete', methods: ['POST'])]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function delete(
        Request $request,
        Club $club,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessClub($club)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer ce club.");
        }

        if ($this->isCsrfTokenValid('delete'.$club->getId(), $request->getPayload()->getString('_token'))) {
            if (
                $club->getAppUsersCount() > 0
                || $club->getTeamsCount() > 0
                || $club->getCategoriesCount() > 0
                || $club->getSeasonsCount() > 0
            ) {
                $this->addFlash(
                    'danger',
                    'Impossible de supprimer ce club : des utilisateurs, équipes, catégories ou saisons y sont associés.'
                );

                return $this->redirectToRoute('app_club_show', [
                    'id' => $club->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($club);
            $entityManager->flush();

            $this->addFlash('success', 'Le club a bien été supprimé.');
        }

        return $this->redirectToRoute('app_club_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canAccessClub(Club $club): bool
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

            return $userClub !== null
                && $userClub->getId() === $club->getId();
        }

        return false;
    }
}
<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Form\AppUserType;
use App\Repository\AppUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/app/user')]
#[IsGranted('ROLE_ADMIN')]
final class AppUserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(AppUserRepository $appUserRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('app_user/index.html.twig', [
            'app_users' => $appUserRepository->findVisibleForUser($currentUser),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $appUser = new AppUser();

        $form = $this->createForm(AppUserType::class, $appUser, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessAppUser($appUser)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas créer cet utilisateur.");
            }

            $plainPassword = $form->get('plainPassword')->getData();

            $hashedPassword = $passwordHasher->hashPassword($appUser, $plainPassword);
            $appUser->setPassword($hashedPassword);

            $entityManager->persist($appUser);
            $entityManager->flush();

            $this->addFlash('success', "L'utilisateur a bien été créé.");

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('app_user/new.html.twig', [
            'app_user' => $appUser,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(AppUser $appUser): Response
    {
        if (!$this->canAccessAppUser($appUser)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas accéder à cet utilisateur.");
        }

        return $this->render('app_user/show.html.twig', [
            'app_user' => $appUser,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        AppUser $appUser,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        if (!$this->canAccessAppUser($appUser)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier cet utilisateur.");
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(AppUserType::class, $appUser, [
            'is_edit' => true,
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessAppUser($appUser)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas rattacher cet utilisateur à ce club.");
            }

            $plainPassword = $form->get('plainPassword')->getData();

            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($appUser, $plainPassword);
                $appUser->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', "L'utilisateur a bien été modifié.");

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('app_user/edit.html.twig', [
            'app_user' => $appUser,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        AppUser $appUser,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessAppUser($appUser)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer cet utilisateur.");
        }

        if ($this->isCsrfTokenValid('delete'.$appUser->getId(), $request->getPayload()->getString('_token'))) {
            $currentUser = $this->getUser();

            // Sécurité : un utilisateur ne peut pas supprimer son propre compte.
            if ($currentUser instanceof AppUser && $currentUser->getId() === $appUser->getId()) {
                $this->addFlash(
                    'danger',
                    'Impossible de supprimer votre propre compte.'
                );

                return $this->redirectToRoute('app_user_show', [
                    'id' => $appUser->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($appUser);
            $entityManager->flush();

            $this->addFlash('success', "L'utilisateur a bien été supprimé.");
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canAccessAppUser(AppUser $appUser): bool
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            return false;
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            // Un admin de club ne doit pas gérer un super admin.
            if (in_array('ROLE_SUPER_ADMIN', $appUser->getRoles(), true)) {
                return false;
            }

            $currentUserClub = $currentUser->getClub();
            $targetUserClub = $appUser->getClub();

            return $currentUserClub !== null
                && $targetUserClub !== null
                && $currentUserClub->getId() === $targetUserClub->getId();
        }

        return false;
    }
}
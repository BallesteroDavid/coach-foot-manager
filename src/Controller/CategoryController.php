<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/category')]
#[IsGranted('ROLE_ADMIN')]
final class CategoryController extends AbstractController
{
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findVisibleForUser($currentUser),
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $category = new Category();

        $form = $this->createForm(CategoryType::class, $category, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessCategory($category)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas créer une catégorie pour ce club.");
            }

            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie a bien été créée.');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_show', methods: ['GET'])]
    public function show(Category $category): Response
    {
        if (!$this->canAccessCategory($category)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas accéder à cette catégorie.");
        }

        return $this->render('category/show.html.twig', [
            'category' => $category,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessCategory($category)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier cette catégorie.");
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CategoryType::class, $category, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessCategory($category)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas rattacher cette catégorie à ce club.");
            }

            $category->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'La catégorie a bien été modifiée.');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessCategory($category)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer cette catégorie.");
        }

        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            if ($category->getTeams()->count() > 0) {
                $this->addFlash(
                    'danger',
                    'Impossible de supprimer cette catégorie : une ou plusieurs équipes y sont associées.'
                );

                return $this->redirectToRoute('app_category_show', [
                    'id' => $category->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($category);
            $entityManager->flush();

            $this->addFlash('success', 'La catégorie a bien été supprimée.');
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canAccessCategory(Category $category): bool
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
            $categoryClub = $category->getClub();

            return $userClub !== null
                && $categoryClub !== null
                && $userClub->getId() === $categoryClub->getId();
        }

        return false;
    }
}
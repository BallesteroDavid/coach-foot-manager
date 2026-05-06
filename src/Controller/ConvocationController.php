<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\Convocation;
use App\Entity\FootballMatch;
use App\Form\ConvocationType;
use App\Repository\ConvocationRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/convocation')]
final class ConvocationController extends AbstractController
{
    #[Route(name: 'app_convocation_index', methods: ['GET'])]
    public function index(ConvocationRepository $convocationRepository): Response
    {
        return $this->render('convocation/index.html.twig', [
            'convocations' => $convocationRepository->findBy([], [
                'createdAt' => 'DESC',
            ]),
        ]);
    }

    #[Route('/new', name: 'app_convocation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $convocation = new Convocation();

        $currentUser = $this->getUser();
        if ($currentUser instanceof AppUser) {
            $convocation->setCreatedBy($currentUser);
        }

        $form = $this->createForm(ConvocationType::class, $convocation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($convocation);
                $entityManager->flush();

                $this->addFlash('success', 'La convocation a bien été créée.');

                return $this->redirectToRoute('app_convocation_index', [], Response::HTTP_SEE_OTHER);
            } catch (UniqueConstraintViolationException) {
                $this->addFlash(
                    'danger',
                    'Ce joueur est déjà convoqué pour ce match.'
                );
            }
        }

        return $this->render('convocation/new.html.twig', [
            'convocation' => $convocation,
            'form' => $form,
        ]);
    }

    #[Route('/match/{id}/create-for-team', name: 'app_convocation_create_for_team', methods: ['POST'])]
    public function createForTeam(
        Request $request,
        FootballMatch $footballMatch,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('create_convocations_for_team'.$footballMatch->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_football_match_show', [
                'id' => $footballMatch->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $team = $footballMatch->getTeam();

        if ($team === null) {
            $this->addFlash('danger', 'Impossible de créer les convocations : aucune équipe associée au match.');

            return $this->redirectToRoute('app_football_match_show', [
                'id' => $footballMatch->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $currentUser = $this->getUser();
        $createdBy = $currentUser instanceof AppUser ? $currentUser : null;

        $createdCount = 0;

        foreach ($team->getPlayers() as $player) {
            $alreadyCalled = false;

            foreach ($footballMatch->getConvocations() as $existingConvocation) {
                if ($existingConvocation->getPlayer() === $player) {
                    $alreadyCalled = true;
                    break;
                }
            }

            if ($alreadyCalled) {
                continue;
            }

            $convocation = new Convocation();
            $convocation->setFootballMatch($footballMatch);
            $convocation->setPlayer($player);
            $convocation->setStatus('called');
            $convocation->setCreatedBy($createdBy);

            $entityManager->persist($convocation);
            $createdCount++;
        }

        $entityManager->flush();

        if ($createdCount > 0) {
            $this->addFlash('success', $createdCount.' convocation(s) créée(s).');
        } else {
            $this->addFlash('warning', 'Aucune convocation créée : tous les joueurs de cette équipe étaient déjà convoqués.');
        }

        return $this->redirectToRoute('app_football_match_show', [
            'id' => $footballMatch->getId(),
        ], Response::HTTP_SEE_OTHER);
    }

    #[Route('/match/{id}/new', name: 'app_convocation_new_from_match', methods: ['GET', 'POST'])]
    public function newFromMatch(
        Request $request,
        FootballMatch $footballMatch,
        EntityManagerInterface $entityManager
    ): Response {
        $convocation = new Convocation();
        $convocation->setFootballMatch($footballMatch);

        $currentUser = $this->getUser();

        if ($currentUser instanceof AppUser) {
            $convocation->setCreatedBy($currentUser);
        }

        $form = $this->createForm(ConvocationType::class, $convocation, [
            'hide_football_match' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->persist($convocation);
                $entityManager->flush();

                $this->addFlash('success', 'La convocation a bien été créée.');

                return $this->redirectToRoute('app_football_match_show', [
                    'id' => $footballMatch->getId(),
                ], Response::HTTP_SEE_OTHER);
            } catch (UniqueConstraintViolationException) {
                $this->addFlash(
                    'danger',
                    'Ce joueur est déjà convoqué pour ce match.'
                );
            }
        }

        return $this->render('convocation/new.html.twig', [
            'convocation' => $convocation,
            'form' => $form,
            'football_match' => $footballMatch,
        ]);
    }

    #[Route('/{id}', name: 'app_convocation_show', methods: ['GET'])]
    public function show(Convocation $convocation): Response
    {
        return $this->render('convocation/show.html.twig', [
            'convocation' => $convocation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_convocation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Convocation $convocation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ConvocationType::class, $convocation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $convocation->setUpdatedAt(new \DateTimeImmutable());

            try {
                $entityManager->flush();

                $this->addFlash('success', 'La convocation a bien été modifiée.');

                return $this->redirectToRoute('app_convocation_index', [], Response::HTTP_SEE_OTHER);
            } catch (UniqueConstraintViolationException) {
                $this->addFlash(
                    'danger',
                    'Ce joueur est déjà convoqué pour ce match.'
                );
            }
        }

        return $this->render('convocation/edit.html.twig', [
            'convocation' => $convocation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_convocation_delete', methods: ['POST'])]
    public function delete(Request $request, Convocation $convocation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$convocation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($convocation);
            $entityManager->flush();

            $this->addFlash('success', 'La convocation a bien été supprimée.');
        }

        return $this->redirectToRoute('app_convocation_index', [], Response::HTTP_SEE_OTHER);
    }
}
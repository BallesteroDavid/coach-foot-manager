<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Entity\FootballMatch;
use App\Form\FootballMatchType;
use App\Repository\FootballMatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/football-match')]
#[IsGranted('ROLE_COACH')]
final class FootballMatchController extends AbstractController
{
    #[Route(name: 'app_football_match_index', methods: ['GET'])]
    public function index(FootballMatchRepository $footballMatchRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('football_match/index.html.twig', [
            'football_matches' => $footballMatchRepository->findVisibleForUser($currentUser),
        ]);
    }

    #[Route('/new', name: 'app_football_match_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $footballMatch = new FootballMatch();

        $form = $this->createForm(FootballMatchType::class, $footballMatch, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessFootballMatch($footballMatch)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas créer un match pour cette équipe.");
            }

            $entityManager->persist($footballMatch);
            $entityManager->flush();

            $this->addFlash('success', 'Le match a bien été créé.');

            return $this->redirectToRoute('app_football_match_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('football_match/new.html.twig', [
            'football_match' => $footballMatch,
            'form' => $form,
            'is_return_creation' => false,
            'first_match' => null,
        ]);
    }

    #[Route('/{id}/return/new', name: 'app_football_match_new_return', methods: ['GET', 'POST'])]
    public function newReturn(
        Request $request,
        FootballMatch $firstMatch,
        EntityManagerInterface $entityManager
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->canAccessFootballMatch($firstMatch)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas créer un match retour pour ce match.");
        }

        // Sécurité : on ne peut créer un match retour que depuis un match de type "aller".
        if ($firstMatch->getMatchType() !== 'aller') {
            $this->addFlash('danger', 'Un match retour ne peut être créé que depuis un match aller.');

            return $this->redirectToRoute('app_football_match_show', [
                'id' => $firstMatch->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        // Sécurité : on évite de créer plusieurs matchs retour pour le même match aller.
        if ($firstMatch->getReturnMatches()->count() > 0) {
            $this->addFlash('warning', 'Ce match possède déjà un match retour associé.');

            return $this->redirectToRoute('app_football_match_show', [
                'id' => $firstMatch->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        $returnMatch = new FootballMatch();

        // Pré-remplissage du match retour à partir du match aller.
        $returnMatch->setTeam($firstMatch->getTeam());
        $returnMatch->setOpponent($firstMatch->getOpponent());
        $returnMatch->setCompetition($firstMatch->getCompetition());
        $returnMatch->setStartTime($firstMatch->getStartTime());
        $returnMatch->setStatus('planned');
        $returnMatch->setMatchType('retour');
        $returnMatch->setFirstMatch($firstMatch);

        // On inverse le type de lieu pour le match retour.
        $returnMatch->setLocationType(match ($firstMatch->getLocationType()) {
            'home' => 'away',
            'away' => 'home',
            default => 'neutral',
        });

        $form = $this->createForm(FootballMatchType::class, $returnMatch, [
            'is_return_creation' => true,
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessFootballMatch($returnMatch)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas créer ce match retour.");
            }

            $entityManager->persist($returnMatch);
            $entityManager->flush();

            $this->addFlash('success', 'Le match retour a bien été créé.');

            return $this->redirectToRoute('app_football_match_show', [
                'id' => $returnMatch->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('football_match/new.html.twig', [
            'football_match' => $returnMatch,
            'form' => $form,
            'is_return_creation' => true,
            'first_match' => $firstMatch,
        ]);
    }

    #[Route('/{id}', name: 'app_football_match_show', methods: ['GET'])]
    public function show(FootballMatch $footballMatch): Response
    {
        if (!$this->canAccessFootballMatch($footballMatch)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas accéder à ce match.");
        }

        return $this->render('football_match/show.html.twig', [
            'football_match' => $footballMatch,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_football_match_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        FootballMatch $footballMatch,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessFootballMatch($footballMatch)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas modifier ce match.");
        }

        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(FootballMatchType::class, $footballMatch, [
            'current_user' => $currentUser,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->canAccessFootballMatch($footballMatch)) {
                throw $this->createAccessDeniedException("Vous ne pouvez pas rattacher ce match à cette équipe.");
            }

            $footballMatch->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Le match a bien été modifié.');

            return $this->redirectToRoute('app_football_match_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('football_match/edit.html.twig', [
            'football_match' => $footballMatch,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_football_match_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        FootballMatch $footballMatch,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->canAccessFootballMatch($footballMatch)) {
            throw $this->createAccessDeniedException("Vous ne pouvez pas supprimer ce match.");
        }

        if ($this->isCsrfTokenValid('delete'.$footballMatch->getId(), $request->getPayload()->getString('_token'))) {
            if ($footballMatch->getReturnMatches()->count() > 0) {
                $this->addFlash(
                    'danger',
                    'Impossible de supprimer ce match : un ou plusieurs matchs retour sont liés à ce match.'
                );

                return $this->redirectToRoute('app_football_match_show', [
                    'id' => $footballMatch->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($footballMatch);
            $entityManager->flush();

            $this->addFlash('success', 'Le match a bien été supprimé.');
        }

        return $this->redirectToRoute('app_football_match_index', [], Response::HTTP_SEE_OTHER);
    }

    private function canAccessFootballMatch(FootballMatch $footballMatch): bool
    {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            return false;
        }

        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return true;
        }

        $team = $footballMatch->getTeam();

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
}
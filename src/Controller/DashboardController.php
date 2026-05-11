<?php

namespace App\Controller;

use App\Entity\AppUser;
use App\Repository\ConvocationRepository;
use App\Repository\FootballMatchRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(
        TeamRepository $teamRepository,
        PlayerRepository $playerRepository,
        FootballMatchRepository $footballMatchRepository,
        ConvocationRepository $convocationRepository,
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser instanceof AppUser) {
            throw $this->createAccessDeniedException();
        }

        $teams = $teamRepository->findVisibleForUser($currentUser);
        $players = $playerRepository->findVisibleForUser($currentUser);
        $footballMatches = $footballMatchRepository->findVisibleForUser($currentUser);
        $convocations = $convocationRepository->findVisibleForUser($currentUser);

        $today = new \DateTimeImmutable('today');

        $upcomingMatchesCount = count(array_filter($footballMatches, function ($footballMatch) use ($today): bool {
            return $footballMatch->getMatchDate() >= $today
                && $footballMatch->getStatus() === 'planned';
        }));

        return $this->render('dashboard/index.html.twig', [
            'teams_count' => count($teams),
            'players_count' => count($players),
            'upcoming_matches_count' => $upcomingMatchesCount,
            'convocations_count' => count($convocations),
        ]);
    }
}
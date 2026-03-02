<?php

namespace App\Controller\Api;

use App\Repository\TeamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LeaderboardApiController extends AbstractController
{
    #[Route('/api/leaderboard', name: 'api_leaderboard', methods: ['GET'])]
    public function getLeaderboard(TeamRepository $teamRepository): JsonResponse
    {
        // 1. Fetch all teams
        $teams = $teamRepository->findAll();
        $data = [];

        foreach ($teams as $team) {
            // 2. Map entity data to a simple array for JSON
            // ✅ Fix :28 & :29 — use nullsafe operator instead of ternary
            // PHPStan considers $stats always non-null from PHPDoc,
            // so the ternary was flagged as "always true".
            // Using ?-> with ?? default avoids the false positive entirely.
            // ✅ Fix :31 & :32 — getStats() is typed as non-nullable TeamStats
            // PHPStan knows it's never null, so use -> directly (no ?-> or ??)
            $stats = $team->getStats();
            $data[] = [
                'id'   => $team->getId(),
                'name' => $team->getName(),
                'logo' => $team->getLogo(),
                'elo'  => $stats->getEloRating(),
                'wins' => $stats->getTotalWins(),
            ];
        }

        // 3. Sort by ELO descending (highest first)
        usort($data, fn($a, $b) => $b['elo'] <=> $a['elo']);

        // 4. Return the JSON response
        return new JsonResponse($data, Response::HTTP_OK);
    }
}
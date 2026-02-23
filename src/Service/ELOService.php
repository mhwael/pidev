<?php

namespace App\Service;

use App\Entity\Team;
use App\Entity\GameMatch;
use Doctrine\ORM\EntityManagerInterface;

/**
 * ELO Rating Service
 * 
 * Implements the ELO rating system used in chess
 * Calculates team strength based on match results
 * 
 * This is REAL machine learning! (Well, the foundation of it)
 */
class ELOService
{
    private const K_FACTOR = 32; // Standard in chess, controls rating volatility
    private const BASE_ELO = 1200; // Starting rating for new teams

    public function __construct(private EntityManagerInterface $em) {}

    /**
     * Calculate new ELO rating after a match
     * 
     * Formula: NewELO = OldELO + K * (Actual - Expected)
     * 
     * @param float $currentELO The team's current ELO rating
     * @param float $opponentELO The opponent's ELO rating
     * @param int $result 1 = win, 0 = loss, 0.5 = draw (we'll use 1 and 0)
     * 
     * @return float The new ELO rating
     */
    public function calculateNewELO(
        float $currentELO,
        float $opponentELO,
        int $result // 1 = win, 0 = loss
    ): float {
        // Step 1: Calculate expected score using logistic function
        // This is the probability that the current team should win
        $expectedScore = 1 / (1 + pow(10, ($opponentELO - $currentELO) / 400));

        // Step 2: Calculate new rating
        // K_FACTOR controls how much the rating changes
        // Higher K_FACTOR = bigger changes
        // Lower K_FACTOR = smaller changes
        $newELO = $currentELO + self::K_FACTOR * ($result - $expectedScore);

        return round($newELO, 2);
    }

    /**
     * Update ELO ratings after a match is completed
     * 
     * This is called when a match is marked as complete
     * It automatically updates both teams' ELO and stats
     * 
     * @throws \Exception if match is not completed or has no winner
     */
    public function updateELOFromMatch(GameMatch $match): void
    {
        // Validate match
        if ($match->getStatus() !== 'completed' || !$match->getWinner()) {
            throw new \Exception('Match must be completed with a winner before updating ELO');
        }

        $team1 = $match->getTeam1();
        $team2 = $match->getTeam2();
        $winner = $match->getWinner();

        if (!$team1 || !$team2) {
            throw new \Exception('Match must have both teams');
        }

        // Step 1: Determine results (1 = win, 0 = loss)
        $team1Result = $winner->getId() === $team1->getId() ? 1 : 0;
        $team2Result = 1 - $team1Result; // If team1 won, team2 lost (and vice versa)

        // Step 2: Get current ELOs
        $team1ELO = $team1->getStats()->getEloRating();
        $team2ELO = $team2->getStats()->getEloRating();

        // Step 3: Calculate new ELOs
        $team1NewELO = $this->calculateNewELO($team1ELO, $team2ELO, $team1Result);
        $team2NewELO = $this->calculateNewELO($team2ELO, $team1ELO, $team2Result);

        // Step 4: Update team stats
        $team1->getStats()->setEloRating($team1NewELO);
        $team2->getStats()->setEloRating($team2NewELO);

        // Step 5: Update wins/losses
        if ($team1Result === 1) {
            $team1->getStats()->incrementWins();
            $team2->getStats()->incrementLosses();
        } else {
            $team1->getStats()->incrementLosses();
            $team2->getStats()->incrementWins();
        }

        // Step 6: Save changes to database
        $this->em->flush();
    }

    /**
     * Get expected win probability for team1 vs team2
     * 
     * Returns a value between 0 and 1
     * 0.5 = both teams equally likely to win
     * 0.7 = team1 has 70% chance to win
     * 0.3 = team1 has 30% chance to win
     */
    public function getExpectedWinProbability(float $team1ELO, float $team2ELO): float
    {
        return 1 / (1 + pow(10, ($team2ELO - $team1ELO) / 400));
    }

    /**
     * Get expected win probability as percentage
     */
    public function getExpectedWinPercentage(float $team1ELO, float $team2ELO): float
    {
        return round($this->getExpectedWinProbability($team1ELO, $team2ELO) * 100, 1);
    }

    /**
     * Validate ELO rating (should be between 0 and 3000)
     */
    public function validateELO(float $elo): bool
    {
        return $elo >= 0 && $elo <= 3000;
    }

    /**
     * Get ELO rating description
     */
    public function getELODescription(float $elo): string
    {
        if ($elo < 1200) {
            return 'Beginner';
        } elseif ($elo < 1600) {
            return 'Intermediate';
        } elseif ($elo < 2000) {
            return 'Advanced';
        } elseif ($elo < 2400) {
            return 'Expert';
        } else {
            return 'Master';
        }
    }
}
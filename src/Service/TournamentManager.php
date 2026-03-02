<?php

namespace App\Service;

use App\Entity\Tournament;

class TournamentManager
{
    private const VALID_STATUSES = ['draft', 'open', 'ongoing', 'completed', 'cancelled'];
    private const MAX_TEAMS_LIMIT = 128;
    private const MIN_NAME_LENGTH = 3;

    public function validate(Tournament $tournament): bool
    {
        // Rule 1: Tournament name is required
        if (empty($tournament->getName())) {
            throw new \InvalidArgumentException('Le nom du tournoi est obligatoire.');
        }

        // Rule 2: Name must be at least 3 characters
        if (strlen($tournament->getName()) < self::MIN_NAME_LENGTH) {
            throw new \InvalidArgumentException('Le nom du tournoi doit contenir au moins 3 caractères.');
        }

        // Rule 3: Max teams must be greater than 0
        if ($tournament->getMaxTeams() <= 0) {
            throw new \InvalidArgumentException('Le nombre maximum d\'équipes doit être supérieur à zéro.');
        }

        // Rule 4: Max teams cannot exceed 128
        if ($tournament->getMaxTeams() > self::MAX_TEAMS_LIMIT) {
            throw new \InvalidArgumentException('Le nombre maximum d\'équipes ne peut pas dépasser 128.');
        }

        // Rule 5: Status must be a valid value
        if ($tournament->getStatus() !== null && !in_array($tournament->getStatus(), self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException('Le statut du tournoi est invalide.');
        }

        // Rule 6: End date must be after start date
        if (
            $tournament->getStartDate() !== null &&
            $tournament->getEndDate() !== null &&
            $tournament->getEndDate() <= $tournament->getStartDate()
        ) {
            throw new \InvalidArgumentException('La date de fin doit être postérieure à la date de début.');
        }

        return true;
    }
}
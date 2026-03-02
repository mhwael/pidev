<?php

namespace App\Tests\Service;

use App\Entity\Tournament;
use App\Service\TournamentManager;
use PHPUnit\Framework\TestCase;

class TournamentManagerTest extends TestCase
{
    private TournamentManager $manager;

    protected function setUp(): void
    {
        $this->manager = new TournamentManager();
    }

    // ── TEST 1 : Tournoi valide ────────────────────────────────────────────
    public function testValidTournament(): void
    {
        $tournament = new Tournament();
        $tournament->setName('Champions Cup');
        $tournament->setMaxTeams(8);
        $tournament->setStartDate(new \DateTime('2025-06-01'));
        $tournament->setEndDate(new \DateTime('2025-06-30'));
        $tournament->setStatus('draft');

        $this->assertTrue($this->manager->validate($tournament));
    }

    // ── TEST 2 : Nom vide ──────────────────────────────────────────────────
    public function testTournamentWithoutName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du tournoi est obligatoire.');

        $tournament = new Tournament();
        $tournament->setName('');
        $tournament->setMaxTeams(8);

        $this->manager->validate($tournament);
    }

    // ── TEST 3 : Nom trop court (< 3 caractères) ───────────────────────────
    public function testTournamentWithTooShortName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du tournoi doit contenir au moins 3 caractères.');

        $tournament = new Tournament();
        $tournament->setName('AB'); // only 2 chars
        $tournament->setMaxTeams(8);

        $this->manager->validate($tournament);
    }

    // ── TEST 4 : maxTeams = 0 ─────────────────────────────────────────────
    public function testTournamentWithZeroMaxTeams(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre maximum d\'équipes doit être supérieur à zéro.');

        $tournament = new Tournament();
        $tournament->setName('Champions Cup');
        $tournament->setMaxTeams(0);

        $this->manager->validate($tournament);
    }

    // ── TEST 5 : maxTeams > 128 ───────────────────────────────────────────
    public function testTournamentWithTooManyTeams(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre maximum d\'équipes ne peut pas dépasser 128.');

        $tournament = new Tournament();
        $tournament->setName('Champions Cup');
        $tournament->setMaxTeams(200); // exceeds limit

        $this->manager->validate($tournament);
    }

    // ── TEST 6 : Status invalide ──────────────────────────────────────────
    public function testTournamentWithInvalidStatus(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le statut du tournoi est invalide.');

        $tournament = new Tournament();
        $tournament->setName('Champions Cup');
        $tournament->setMaxTeams(8);
        $tournament->setStatus('invalid_status'); // not in allowed list

        $this->manager->validate($tournament);
    }

    // ── TEST 7 : Date de fin avant date de début ──────────────────────────
    public function testTournamentWithInvalidDates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début.');

        $tournament = new Tournament();
        $tournament->setName('Champions Cup');
        $tournament->setMaxTeams(8);
        $tournament->setStartDate(new \DateTime('2025-06-30'));
        $tournament->setEndDate(new \DateTime('2025-06-01')); // before start!

        $this->manager->validate($tournament);
    }
}
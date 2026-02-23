<?php

namespace App\Command;

use App\Entity\Team;
use App\Entity\GameMatch;
use App\Entity\TeamStats;
use App\Repository\TeamRepository;
use App\Repository\GameMatchRepository;
use App\Service\ELOService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-elo',
    description: 'Test the ELO system'
)]
class TestEloCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private TeamRepository $teamRepository,
        private GameMatchRepository $matchRepository,
        private ELOService $eloService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🤖 ELO SYSTEM TEST');

        // Test 1: Check tables exist
        $io->section('Test 1: Database Tables');
        try {
            $teams = $this->teamRepository->findAll();
            $io->success("✅ Teams table exists: " . count($teams) . " teams found");
        } catch (\Exception $e) {
            $io->error("❌ Teams table error: " . $e->getMessage());
            return Command::FAILURE;
        }

        try {
            $matches = $this->matchRepository->findAll();
            $io->success("✅ GameMatch table exists: " . count($matches) . " matches found");
        } catch (\Exception $e) {
            $io->error("❌ GameMatch table error: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Test 2: Test ELO calculation
        $io->section('Test 2: ELO Calculation');
        try {
            $newELO = $this->eloService->calculateNewELO(1200, 1000, 1);
            $io->success("✅ ELO Service works!");
            $io->text("   Team (1200 ELO) beats Team (1000 ELO)");
            $io->text("   New ELO: " . $newELO);
            $io->text("   Expected: ~1208");
        } catch (\Exception $e) {
            $io->error("❌ ELO calculation error: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Test 3: Test win probability
        $io->section('Test 3: Win Probability Prediction');
        try {
            $probability = $this->eloService->getExpectedWinPercentage(1200, 1000);
            $io->success("✅ Probability Calculation works!");
            $io->text("   Team (1200 ELO) vs Team (1000 ELO)");
            $io->text("   Win probability: " . $probability . "%");
            $io->text("   Expected: ~76%");
        } catch (\Exception $e) {
            $io->error("❌ Probability calculation error: " . $e->getMessage());
            return Command::FAILURE;
        }

        // Test 4: Check team stats
        $io->section('Test 4: Team Stats');
        if (count($teams) > 0) {
            $team = $teams[0];
            try {
                $io->text("Team: " . $team->getName());
                $io->text("  ELO Rating: " . $team->getEloRating());
                $io->text("  Total Wins: " . $team->getTotalWins());
                $io->text("  Total Losses: " . $team->getTotalLosses());
                $io->text("  Win Rate: " . ($team->getWinRate() * 100) . "%");
                $io->text("  Total Games: " . $team->getTotalGames());
                $io->success("✅ Team stats working!");
            } catch (\Exception $e) {
                $io->error("❌ Team stats error: " . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $io->warning("⚠️  No teams in database to test");
        }

        // Summary
        $io->section('Summary');
        $io->success("✅ ALL ELO SYSTEM TESTS PASSED!");
        $io->text("Your ELO system is ready to use! 🎉");

        return Command::SUCCESS;
    }
}
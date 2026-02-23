<?php

namespace App\Command;

use App\Repository\TournamentRepository;
use App\Service\MatchmakingAI;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-matchmaking',
    description: 'Test the AI Matchmaking system'
)]
class TestMatchmakingCommand extends Command
{
    public function __construct(
        private TournamentRepository $tournamentRepository,
        private MatchmakingAI $matchmakingAI,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🤖 AI MATCHMAKING SYSTEM TEST');

        // Get tournament
        $tournament = $this->tournamentRepository->findOneBy([]);
        if (!$tournament) {
            $io->error("❌ No tournament found!");
            return Command::FAILURE;
        }

        $teams = $tournament->getTeams()->toArray();
        if (count($teams) < 2) {
            $io->error("❌ Need at least 2 teams in tournament!");
            return Command::FAILURE;
        }

        $io->section('Tournament: ' . $tournament->getName());
        $io->text("Teams: " . count($teams));

        // Display team strengths
        $io->section('Team Strength Analysis');
        $strengthRows = [];
        foreach ($teams as $team) {
            $strength = $this->matchmakingAI->calculateTeamStrength($team);
            $strengthRows[] = [
                $team->getName(),
                number_format($strength, 2),
                '█' . str_repeat('█', (int)($strength / 2)) . str_repeat('░', (int)((100 - $strength) / 2)),
                $this->getStrengthLabel($strength),
            ];
        }
        usort($strengthRows, function ($a, $b) {
            return floatval($b[1]) <=> floatval($a[1]);
        });

        $io->table(
            ['Team', 'Strength Score', 'Visual', 'Rating'],
            $strengthRows
        );

        // Generate matchups
        $io->section('🎯 AI Generated Matchups');
        $matchups = $this->matchmakingAI->generateMatchups($teams);
        $averageBalance = $this->matchmakingAI->getAverageBalance($matchups);

        $io->text("Tournament Fairness: " . $this->matchmakingAI->getFairnessRating($averageBalance));
        $io->text("Average Balance Score: " . $averageBalance . "%");
        $io->newLine();

        // Display matchups
        foreach ($matchups as $i => $matchup) {
            $team1 = $matchup['team1']->getName();
            $team2 = $matchup['team2']->getName();
            $strength1 = $matchup['strength1'];
            $strength2 = $matchup['strength2'];
            $balance = $matchup['balance'];
            $prediction = $matchup['prediction'];

            // Create a nice display
            $io->writeln("\n");
            $io->writeln("┌─────────────────────────────────────────────────┐");
            $io->writeln("│ <fg=cyan>MATCH " . ($i + 1) . "</> - Balance Score: <fg=yellow>" . $balance . "%</> " . $this->getBalanceEmoji($balance) . "");
            $io->writeln("├─────────────────────────────────────────────────┤");
            $io->writeln("│");
            $io->writeln("│  <fg=green>" . str_pad($team1, 20) . "</> (Strength: " . $strength1 . ")");
            $io->writeln("│  " . str_repeat('█', (int)($strength1 / 3)) . str_repeat('░', 30 - (int)($strength1 / 3)));
            $io->writeln("│");
            $io->writeln("│  <fg=cyan>VS</>");
            $io->writeln("│");
            $io->writeln("│  <fg=blue>" . str_pad($team2, 20) . "</> (Strength: " . $strength2 . ")");
            $io->writeln("│  " . str_repeat('█', (int)($strength2 / 3)) . str_repeat('░', 30 - (int)($strength2 / 3)));
            $io->writeln("│");
            $io->writeln("├─────────────────────────────────────────────────┤");
            $io->writeln("│ <fg=yellow>AI Prediction:</>");
            $io->writeln("│  " . $prediction['favorite_name'] . " has <fg=green>" . $prediction['favorite'] === 'team1' ? $prediction['team1_percentage'] : $prediction['team2_percentage'] . "%</> chance to win");
            $io->writeln("│  Confidence: " . $prediction['confidence'] . "%");
            $io->writeln("│  Match Type: " . $this->getMatchType($balance));
            $io->writeln("└─────────────────────────────────────────────────┘");
        }

        // Summary
        $io->newLine();
        $io->section('Summary');
        $io->success("✅ AI MATCHMAKING COMPLETE!");
        $io->text("Generated " . count($matchups) . " matchups");
        $io->text("Average fairness: " . $averageBalance . "%");
        $io->text("Quality: " . $this->matchmakingAI->getFairnessRating($averageBalance));

        return Command::SUCCESS;
    }

    private function getStrengthLabel(float $strength): string
    {
        if ($strength >= 90) {
            return '🏆 Legendary';
        } elseif ($strength >= 80) {
            return '⭐ Expert';
        } elseif ($strength >= 70) {
            return '👍 Advanced';
        } elseif ($strength >= 60) {
            return '📈 Intermediate';
        } else {
            return '🌱 Beginner';
        }
    }

    private function getBalanceEmoji(float $balance): string
    {
        if ($balance >= 90) {
            return '⚖️ Perfect';
        } elseif ($balance >= 80) {
            return '✅ Excellent';
        } elseif ($balance >= 70) {
            return '👌 Good';
        } elseif ($balance >= 60) {
            return '⚠️ Fair';
        } else {
            return '❌ Poor';
        }
    }

    private function getMatchType(float $balance): string
    {
        if ($balance >= 85) {
            return 'Competitive Match (High Interest)';
        } elseif ($balance >= 70) {
            return 'Balanced Match';
        } else {
            return 'Lopsided Match (Possible Upset)';
        }
    }
}
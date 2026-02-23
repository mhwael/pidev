<?php

namespace App\Command;

use App\Entity\GameMatch;
use App\Repository\TeamRepository;
use App\Repository\TournamentRepository;
use App\Service\ELOService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:populate-matches',
    description: 'Populate database with test matches and ELO statistics'
)]
class PopulateMatchesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private TeamRepository $teamRepository,
        private TournamentRepository $tournamentRepository,
        private ELOService $eloService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('📊 POPULATING DATABASE WITH TEST MATCHES');

        // Get teams
        $teams = $this->teamRepository->findAll();
        if (count($teams) < 2) {
            $io->error("❌ Need at least 2 teams to create matches. Found: " . count($teams));
            return Command::FAILURE;
        }

        $io->text("Found " . count($teams) . " teams");

        // Get a tournament
        $tournament = $this->tournamentRepository->findOneBy([]);
        if (!$tournament) {
            $io->error("❌ No tournament found. Please create a tournament first!");
            return Command::FAILURE;
        }

        $io->text("Using tournament: " . $tournament->getName());

        // Create matches between teams
        $io->section('Creating Matches');
        $matchCount = 0;
        $progressBar = $io->createProgressBar(count($teams) * 3);

        for ($round = 1; $round <= 3; $round++) {
            $io->text("\n📍 Round $round");

            // Create random matches
            for ($i = 0; $i < count($teams) - 1; $i++) {
                $team1 = $teams[$i];
                $team2 = $teams[count($teams) - 1 - $i];

                if ($team1->getId() === $team2->getId()) {
                    continue;
                }

                // Random winner
                $winner = rand(0, 1) === 0 ? $team1 : $team2;
                $loser = $winner->getId() === $team1->getId() ? $team2 : $team1;

                // Create match
                $match = new GameMatch();
                $match->setTournament($tournament);
                $match->setTeam1($team1);
                $match->setTeam2($team2);
                $match->setWinner($winner);
                $match->setStatus('completed');
                $match->setRound($round);
                $match->setTeam1Score(rand(15, 30));
                $match->setTeam2Score(rand(5, 25));
                $match->setCompletedAt(new \DateTimeImmutable());

                // Save match
                $this->em->persist($match);
                $matchCount++;

                // Update ELO
                $this->eloService->updateELOFromMatch($match);

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->em->flush();

        $io->newLine(2);
        $io->success("✅ Created $matchCount matches!");

        // Display team stats
        $io->section('Team Statistics After Matches');

        $teams = $this->teamRepository->findAll();
        
        $rows = [];
        foreach ($teams as $team) {
            $rows[] = [
                $team->getName(),
                number_format($team->getEloRating(), 2),
                $team->getTotalWins(),
                $team->getTotalLosses(),
                $team->getTotalGames(),
                round($team->getWinRate() * 100, 2) . '%',
            ];
        }

        $io->table(
            ['Team', 'ELO Rating', 'Wins', 'Losses', 'Total Games', 'Win Rate'],
            $rows
        );

        $io->section('Summary');
        $io->success("✅ DATABASE POPULATED SUCCESSFULLY!");
        $io->text("Created $matchCount matches across " . count($teams) . " teams");
        $io->text("Your ELO system is now fully tested with real data! 🎉");

        return Command::SUCCESS;
    }
}
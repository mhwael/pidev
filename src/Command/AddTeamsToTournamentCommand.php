<?php

namespace App\Command;

use App\Repository\TeamRepository;
use App\Repository\TournamentRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Doctrine\ORM\EntityManagerInterface;

#[AsCommand(
    name: 'app:add-teams-to-tournament',
    description: 'Add all teams to a tournament'
)]
class AddTeamsToTournamentCommand extends Command
{
    public function __construct(
        private TeamRepository $teamRepository,
        private TournamentRepository $tournamentRepository,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('tournament-id', InputArgument::OPTIONAL, 'Tournament ID (optional, uses first tournament if not provided)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🎮 ADD TEAMS TO TOURNAMENT');

        // Get tournament
        $tournamentId = $input->getArgument('tournament-id');
        
        if ($tournamentId) {
            $tournament = $this->tournamentRepository->find($tournamentId);
        } else {
            $tournament = $this->tournamentRepository->findOneBy([]);
        }

        if (!$tournament) {
            $io->error("❌ Tournament not found!");
            return Command::FAILURE;
        }

        $io->text("Tournament: <fg=cyan>" . $tournament->getName() . "</>");

        // Get all teams
        $teams = $this->teamRepository->findAll();
        if (count($teams) === 0) {
            $io->error("❌ No teams found!");
            return Command::FAILURE;
        }

        $io->text("Available teams: " . count($teams));

        // Add teams to tournament
        $io->section('Adding Teams');
        $addedCount = 0;
        $alreadyAddedCount = 0;

        $progressBar = $io->createProgressBar(count($teams));
        $progressBar->start();

        foreach ($teams as $team) {
            if (!$tournament->getTeams()->contains($team)) {
                $tournament->addTeam($team);
                $addedCount++;
            } else {
                $alreadyAddedCount++;
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->em->flush();

        $io->newLine(2);
        $io->success("✅ TEAMS ADDED TO TOURNAMENT!");
        $io->text("Added: <fg=green>$addedCount</> teams");
        if ($alreadyAddedCount > 0) {
            $io->text("Already in tournament: <fg=yellow>$alreadyAddedCount</> teams");
        }

        // Display tournament teams
        $io->section('Tournament Teams');
        $rows = [];
        foreach ($tournament->getTeams() as $team) {
            $rows[] = [
                $team->getName(),
                number_format($team->getEloRating(), 2),
                $team->getTotalWins(),
                $team->getTotalLosses(),
                round($team->getWinRate() * 100, 2) . '%',
            ];
        }

        $io->table(
            ['Team Name', 'ELO', 'Wins', 'Losses', 'Win Rate'],
            $rows
        );

        $io->text("\n<fg=cyan>Total teams in tournament: " . count($tournament->getTeams()) . "</>");

        return Command::SUCCESS;
    }
}
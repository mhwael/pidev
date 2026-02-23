<?php

namespace App\Repository;

use App\Entity\GameMatch;
use App\Entity\Tournament;
use App\Entity\Team;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMatch>
 */
class GameMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMatch::class);
    }

    /**
     * Find all matches in a tournament
     */
    public function findByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->setParameter('tournament', $tournament)
            ->orderBy('m.round', 'ASC')
            ->addOrderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find completed matches in a tournament
     */
    public function findCompletedByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('m.status = :status')
            ->setParameter('tournament', $tournament)
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find pending matches in a tournament
     */
    public function findPendingByTournament(Tournament $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('m.status = :status')
            ->setParameter('tournament', $tournament)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find matches for a specific team in a tournament
     */
    public function findByTeamAndTournament(Team $team, Tournament $tournament): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.tournament = :tournament')
            ->andWhere('(m.team1 = :team OR m.team2 = :team)')
            ->setParameter('tournament', $tournament)
            ->setParameter('team', $team)
            ->orderBy('m.round', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all matches involving a team
     */
    public function findByTeam(Team $team, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.team1 = :team OR m.team2 = :team')
            ->setParameter('team', $team)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find matches between two teams
     */
    public function findMatchesBetweenTeams(Team $team1, Team $team2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.team1 = :team1 AND m.team2 = :team2) OR (m.team1 = :team2 AND m.team2 = :team1)')
            ->setParameter('team1', $team1)
            ->setParameter('team2', $team2)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find match count by round in tournament
     */
    public function countByRound(Tournament $tournament, int $round): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.tournament = :tournament')
            ->andWhere('m.round = :round')
            ->setParameter('tournament', $tournament)
            ->setParameter('round', $round)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get latest matches
     */
    public function findLatestMatches(int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get completed matches count
     */
    public function countCompleted(): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.status = :status')
            ->setParameter('status', 'completed')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
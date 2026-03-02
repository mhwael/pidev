<?php

namespace App\Repository;

use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
class GameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    /**
     * @return Game[]
     */
    public function searchGames(string $searchTerm): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.name LIKE :term')
            ->orWhere('g.slug LIKE :term')
            ->setParameter('term', '%'.$searchTerm.'%')
            ->getQuery()
            ->getResult();
    }
}
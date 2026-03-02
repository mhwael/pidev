<?php

namespace App\Repository;

use App\Entity\Guide;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Guide>
 */
class GuideRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Guide::class);
    }

    /**
     * @return Guide[]
     */
    public function findAllOptimized(): array
    {
        return $this->createQueryBuilder('g')
            ->addSelect('a', 'gm') 
            ->leftJoin('g.author', 'a') 
            ->leftJoin('g.game', 'gm')
            ->getQuery()
            ->getResult();
    }
}
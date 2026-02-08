<?php

namespace App\Repository;

use App\Entity\GuideRating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuideRating>
 */
class GuideRatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuideRating::class);
    }

    //    /**
    //     * @return GuideRating[] Returns an array of GuideRating objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('g.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?GuideRating
    //    {
    //        return $this->createQueryBuilder('g')
    //            ->andWhere('g.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

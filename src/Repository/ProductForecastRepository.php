<?php

namespace App\Repository;

use App\Entity\ProductForecast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProductForecastRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductForecast::class);
    }

    /**
     * Returns the latest forecast per product for a given forecastDays (ex: 7).
     * Output: [ productId => ProductForecast ]
     */
    public function findLatestByProductIds(array $productIds, int $forecastDays = 7): array
    {
        if (!$productIds) {
            return [];
        }

        // latest generatedAt per product
        $rows = $this->createQueryBuilder('pf')
            ->select('IDENTITY(pf.product) AS pid, MAX(pf.generatedAt) AS maxGen')
            ->andWhere('pf.product IN (:ids)')
            ->andWhere('pf.forecastDays = :days')
            ->setParameter('ids', $productIds)
            ->setParameter('days', $forecastDays)
            ->groupBy('pid')
            ->getQuery()
            ->getArrayResult();

        if (!$rows) {
            return [];
        }

        // Build OR conditions to fetch those exact rows
        $orX = [];
        $params = ['days' => $forecastDays];
        $i = 0;

        foreach ($rows as $r) {
            $i++;
            $orX[] = "(IDENTITY(pf2.product) = :p$i AND pf2.generatedAt = :g$i)";
            $params["p$i"] = (int)$r['pid'];
            $params["g$i"] = new \DateTimeImmutable($r['maxGen']);
        }

        $qb2 = $this->createQueryBuilder('pf2')
            ->andWhere('pf2.forecastDays = :days')
            ->andWhere(implode(' OR ', $orX));

        foreach ($params as $k => $v) {
            $qb2->setParameter($k, $v);
        }

        $list = $qb2->getQuery()->getResult();

        $out = [];
        foreach ($list as $pf) {
            /** @var ProductForecast $pf */
            $out[$pf->getProduct()->getId()] = $pf;
        }

        return $out;
    }
}
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
     * @param list<int> $productIds
     * @return array<int, ProductForecast>
     */
    public function findLatestByProductIds(array $productIds, int $forecastDays = 7): array
    {
        if ($productIds === []) return [];

        $rows = $this->createQueryBuilder('pf')
            ->select('IDENTITY(pf.product) AS pid, MAX(pf.generatedAt) AS maxGen')
            ->andWhere('pf.product IN (:ids)')
            ->andWhere('pf.forecastDays = :days')
            ->setParameter('ids', $productIds)
            ->setParameter('days', $forecastDays)
            ->groupBy('pid')
            ->getQuery()
            ->getArrayResult();

        if ($rows === []) return [];

        $orX = [];
        $params = ['days' => $forecastDays];
        $i = 0;

        foreach ($rows as $r) {
            $pid = (int)($r['pid'] ?? 0);
            $maxGen = (string)($r['maxGen'] ?? '');
            if ($pid <= 0 || $maxGen === '') continue;

            $i++;
            $orX[] = "(IDENTITY(pf2.product) = :p$i AND pf2.generatedAt = :g$i)";
            $params["p$i"] = $pid;
            $params["g$i"] = new \DateTimeImmutable($maxGen);
        }

        if ($orX === []) return [];

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
            $prod = $pf->getProduct();
            if ($prod === null) continue;

            $id = $prod->getId();
            if ($id === null) continue;

            $out[(int)$id] = $pf;
        }

        return $out;
    }
}
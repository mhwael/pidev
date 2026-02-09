<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function search(?string $q, ?string $category): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($q) {
            $qb->andWhere('LOWER(p.name) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        if ($category) {
            $qb->andWhere('LOWER(p.category) LIKE :cat')
                ->setParameter('cat', '%' . mb_strtolower($category) . '%');
        }

        return $qb->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findProductsWithOrdersCount(int $limit = 200): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.orders', 'o')
            ->addSelect('COUNT(DISTINCT o.id) AS ordersCount')
            ->groupBy('p.id')
            ->orderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getStockStats(int $lowStockThreshold = 5): array
    {
        $totalProducts = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalStock = (int) $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.stock), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        $outOfStock = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.stock <= 0')
            ->getQuery()
            ->getSingleScalarResult();

        $lowStock = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.stock > 0')
            ->andWhere('p.stock <= :t')
            ->setParameter('t', $lowStockThreshold)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'totalProducts' => $totalProducts,
            'totalStock' => $totalStock,
            'outOfStock' => $outOfStock,
            'lowStock' => $lowStock,
            'lowStockThreshold' => $lowStockThreshold,
        ];
    }

    /**
     * Category stats: how many products in each category
     * Returns: [ ['category' => 'pc items', 'productCount' => 3], ... ]
     */
    public function getCategoryCounts(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.category AS category, COUNT(p.id) AS productCount')
            ->groupBy('p.category')
            ->orderBy('productCount', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $r) {
            $cat = $r['category'];
            if ($cat === null || trim((string)$cat) === '') {
                $cat = 'Uncategorized';
            }
            $out[] = [
                'category' => $cat,
                'productCount' => (int) $r['productCount'],
            ];
        }

        return $out;
    }
}

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

    /**
     * ✅ Used in Shop search (name + real category)
     */
    public function search(?string $q, ?string $category): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($q) {
            $qb->andWhere('LOWER(p.name) LIKE :q')
               ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        // ✅ REAL category filter (exact match, since it comes from dropdown)
        if ($category) {
            $qb->andWhere('p.category = :cat')
               ->setParameter('cat', $category);
        }

        return $qb->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * ✅ Shop dropdown categories (CLEAN fixed list)
     */
    public function getDistinctCategories(): array
    {
        return [
            'Games',
            'Accessories',
            'Consoles',
            'Controllers',
            'Headsets',
            'Gift Cards',
            'Merch',
        ];
    }

    /**
     * ✅ Products + orders count + sorting
     * sort: "default" | "price" | "orders"
     * dir : "asc" | "desc"
     *
     * default => newest first (id desc)
     */
    public function findProductsWithOrdersCount(int $limit = 200, string $sort = 'default', string $dir = 'desc'): array
    {
        $sort = in_array($sort, ['default', 'price', 'orders'], true) ? $sort : 'default';
        $dir  = in_array(strtolower($dir), ['asc', 'desc'], true) ? strtolower($dir) : 'desc';

        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.orders', 'o')
            ->addSelect('COUNT(DISTINCT o.id) AS ordersCount')
            ->groupBy('p.id')
            ->setMaxResults($limit);

        if ($sort === 'price') {
            $qb->orderBy('p.price', $dir)
               ->addOrderBy('p.id', 'DESC');
        } elseif ($sort === 'orders') {
            $qb->orderBy('ordersCount', $dir)
               ->addOrderBy('p.id', 'DESC');
        } else {
            $qb->orderBy('p.id', 'DESC');
        }

        return $qb->getQuery()->getResult();
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

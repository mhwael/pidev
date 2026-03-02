<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\ProductRecommendation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductRecommendation>
 */
class ProductRecommendationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductRecommendation::class);
    }

    /**
     * Latest recommendations for one product.
     *
     * @return list<array{product: Product, score: float}>
     */
    public function getForProduct(Product $product, int $limit = 6): array
    {
        $latest = $this->createQueryBuilder('r')
            ->select('MAX(r.generatedAt)')
            ->andWhere('r.product = :p')
            ->setParameter('p', $product)
            ->getQuery()
            ->getSingleScalarResult();

        if (!$latest) {
            return [];
        }

        $list = $this->createQueryBuilder('r')
            ->leftJoin('r.recommendedProduct', 'rp')
            ->addSelect('rp')
            ->andWhere('r.product = :p')
            ->andWhere('r.generatedAt = :g')
            ->setParameter('p', $product)
            ->setParameter('g', $latest)
            ->orderBy('r.score', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $out = [];
        foreach ($list as $rec) {
            /** @var ProductRecommendation $rec */
            $rp = $rec->getRecommendedProduct();
            if ($rp) {
                $out[] = [
                    'product' => $rp,
                    'score' => (float) $rec->getScore(),
                ];
            }
        }

        return $out;
    }
}
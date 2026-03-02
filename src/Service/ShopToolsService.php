<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class ShopToolsService
{
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function dispatch(string $name, array $args): array
    {
        return match ($name) {
            'get_popular_products' => $this->popular((int)($args['limit'] ?? 6)),
            'get_top_predicted_next7d' => $this->topPredicted((int)($args['limit'] ?? 6)),
            'get_recommendations_for_product' => $this->recommend((int)($args['product_id'] ?? 0), (int)($args['k'] ?? 6)),
            'search_products' => $this->search(
                (string)($args['query'] ?? ''),
                (string)($args['category'] ?? ''),
                isset($args['max_price']) ? (float)$args['max_price'] : null,
                (int)($args['limit'] ?? 8),
            ),
            'get_low_stock' => $this->lowStock((int)($args['threshold'] ?? 5), (int)($args['limit'] ?? 8)),
            default => ['error' => 'Unknown tool'],
        };
    }

    /**
     * @return array{cards: list<array<string, mixed>>, items: list<array<string, mixed>>}
     */
    private function popular(int $limit): array
    {
        $limit = max(1, min(50, $limit));

        $conn = $this->em->getConnection();
        $rows = $conn->fetchAllAssociative("
            SELECT p.id, p.name, p.category, p.price, p.stock,
                   COALESCE(SUM(oi.quantity),0) AS qty_sold
            FROM product p
            LEFT JOIN order_item oi ON oi.product_id = p.id
            GROUP BY p.id
            ORDER BY qty_sold DESC, p.id DESC
            LIMIT $limit
        ");

        return $this->rowsToCards($rows, fn(array $r) => "Sold: {$r['qty_sold']} • {$r['category']}");
    }

    /**
     * @return array{cards: list<array<string, mixed>>, items: list<array<string, mixed>>}
     */
    private function topPredicted(int $limit): array
    {
        $limit = max(1, min(50, $limit));

        $conn = $this->em->getConnection();
        $rows = $conn->fetchAllAssociative("
            SELECT p.id, p.name, p.category, p.price, p.stock,
                   pf.predicted_qty
            FROM product_forecast pf
            INNER JOIN product p ON p.id = pf.product_id
            WHERE pf.forecast_days = 7
              AND pf.generated_at = (
                 SELECT MAX(pf2.generated_at)
                 FROM product_forecast pf2
                 WHERE pf2.product_id = pf.product_id AND pf2.forecast_days = 7
              )
            ORDER BY pf.predicted_qty DESC
            LIMIT $limit
        ");

        return $this->rowsToCards(
            $rows,
            fn(array $r) => "Pred7d: " . number_format((float)$r['predicted_qty'], 2, '.', '') . " • {$r['category']}"
        );
    }

    /**
     * @return array{cards: list<array<string, mixed>>, items: list<array<string, mixed>>}
     */
    private function recommend(int $productId, int $k): array
    {
        if ($productId <= 0) {
            return ['cards' => [], 'items' => []];
        }

        $k = max(1, min(20, $k));

        $conn = $this->em->getConnection();
        $rows = $conn->fetchAllAssociative("
            SELECT p2.id, p2.name, p2.category, p2.price, p2.stock, pr.score
            FROM product_recommendation pr
            INNER JOIN product p2 ON p2.id = pr.recommended_product_id
            WHERE pr.product_id = ?
              AND pr.generated_at = (
                SELECT MAX(pr2.generated_at)
                FROM product_recommendation pr2
                WHERE pr2.product_id = pr.product_id
              )
            ORDER BY pr.score DESC
            LIMIT $k
        ", [$productId]);

        return $this->rowsToCards(
            $rows,
            fn(array $r) => "Score: " . number_format((float)$r['score'], 3, '.', '') . " • {$r['category']}"
        );
    }

    /**
     * @return array{cards: list<array<string, mixed>>, items: list<array<string, mixed>>}
     */
    private function search(string $q, string $category, ?float $maxPrice, int $limit): array
    {
        $limit = max(1, min(50, $limit));

        $conn = $this->em->getConnection();

        $sql = "SELECT id, name, category, price, stock FROM product WHERE 1=1";
        $params = [];

        if ($q !== '') {
            $sql .= " AND LOWER(name) LIKE ?";
            $params[] = '%' . mb_strtolower($q) . '%';
        }
        if ($category !== '') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }
        if ($maxPrice !== null) {
            $sql .= " AND price <= ?";
            $params[] = $maxPrice;
        }

        $sql .= " ORDER BY id DESC LIMIT $limit";

        $rows = $conn->fetchAllAssociative($sql, $params);

        return $this->rowsToCards($rows, fn(array $r) => "{$r['category']} • Stock: {$r['stock']}");
    }

    /**
     * @return array{cards: list<array<string, mixed>>, items: list<array<string, mixed>>}
     */
    private function lowStock(int $threshold, int $limit): array
    {
        $limit = max(1, min(50, $limit));

        $conn = $this->em->getConnection();
        $rows = $conn->fetchAllAssociative("
            SELECT id, name, category, price, stock
            FROM product
            WHERE stock <= ?
            ORDER BY stock ASC, id DESC
            LIMIT $limit
        ", [$threshold]);

        return $this->rowsToCards($rows, fn(array $r) => "Stock: {$r['stock']} • {$r['category']}");
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{cards: list<array{title:string, subtitle:string, meta:string, url:string}>, items: list<array<string, mixed>>}
     */
    private function rowsToCards(array $rows, callable $subtitleFn): array
    {
        $cards = [];
        foreach ($rows as $r) {
            $cards[] = [
                'title' => (string) ($r['name'] ?? ''),
                'subtitle' => (string) $subtitleFn($r),
                'meta' => "Price: {$r['price']} • Stock: {$r['stock']}",
                'url' => "/shop/products/{$r['id']}",
            ];
        }

        return [
            'cards' => $cards,
            'items' => $rows,
        ];
    }
}
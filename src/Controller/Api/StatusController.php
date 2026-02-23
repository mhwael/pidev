<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Entity\Product;
use App\Entity\ProductForecast;
use App\Entity\ProductRecommendation;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/api')]
class StatusController extends AbstractController
{
    #[Route('/status', name: 'api_status', methods: ['GET'])]
    public function status(EntityManagerInterface $em): JsonResponse
    {
        // --- DB + counts
        $dbOk = true;
        $productsCount = 0;
        $ordersCount = 0;

        try {
            $productsCount = (int) $em->getRepository(Product::class)->count([]);
            $ordersCount = (int) $em->getRepository(Order::class)->count([]);
        } catch (\Throwable $e) {
            $dbOk = false;
        }

        // --- last AI refresh timestamps (DB tables)
        $lastForecastAt = null;
        $lastRecoAt = null;

        try {
            $lastForecastAt = $em->createQueryBuilder()
                ->select('MAX(pf.generatedAt)')
                ->from(ProductForecast::class, 'pf')
                ->getQuery()
                ->getSingleScalarResult();

            $lastRecoAt = $em->createQueryBuilder()
                ->select('MAX(pr.generatedAt)')
                ->from(ProductRecommendation::class, 'pr')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Throwable $e) {
            // ignore
        }

        // --- ML API health
        $mlOk = false;
        $mlTime = null;
        $mlError = null;

        try {
            $base = rtrim((string) ($_ENV['ML_API_BASE_URL'] ?? ''), '/');
            if ($base !== '') {
                $client = HttpClient::create();
                $res = $client->request('GET', $base . '/health', ['timeout' => 3]);
                $arr = $res->toArray(false);

                $mlOk = (bool)($arr['ok'] ?? false);
                $mlTime = $arr['time'] ?? null;
            } else {
                $mlError = 'ML_API_BASE_URL is not set';
            }
        } catch (\Throwable $e) {
            $mlOk = false;
            $mlError = $e->getMessage();
        }

        return $this->json([
            'app' => 'LevelUp',
            'symfony' => 'ok',
            'db' => $dbOk ? 'ok' : 'error',
            'ml_api' => $mlOk ? 'ok' : 'down',
            'counts' => [
                'products' => $productsCount,
                'orders' => $ordersCount,
            ],
            'ml' => [
                'health_time' => $mlTime,
                'last_forecast_at' => $lastForecastAt ?: null,
                'last_reco_at' => $lastRecoAt ?: null,
                'error' => $mlError,
            ],
        ]);
    }
}
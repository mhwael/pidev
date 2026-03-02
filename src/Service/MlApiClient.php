<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MlApiClient
{
    public function __construct(
        private HttpClientInterface $http,
        private string $baseUrl
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function health(): array
    {
        $res = $this->http->request('GET', rtrim($this->baseUrl, '/') . "/health", [
            'timeout' => 5,
        ]);

        /** @var array<string, mixed> $out */
        $out = $res->toArray(false);
        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecommendations(int $productId, int $k = 6): array
    {
        $res = $this->http->request('GET', rtrim($this->baseUrl, '/') . "/recommend/$productId", [
            'query' => ['k' => $k],
            'timeout' => 10,
        ]);

        /** @var array<string, mixed> $out */
        $out = $res->toArray(false);
        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function getForecast(int $productId, int $days = 7): array
    {
        $res = $this->http->request('GET', rtrim($this->baseUrl, '/') . "/forecast/$productId", [
            'query' => ['days' => $days],
            'timeout' => 10,
        ]);

        /** @var array<string, mixed> $out */
        $out = $res->toArray(false);
        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshRecommendations(int $k = 6): array
    {
        $res = $this->http->request('POST', rtrim($this->baseUrl, '/') . "/refresh/recommendations", [
            'query' => ['k' => $k],
            'timeout' => 60,
        ]);

        /** @var array<string, mixed> $out */
        $out = $res->toArray(false);
        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshForecasts(int $forecastDays = 7): array
    {
        $res = $this->http->request('POST', rtrim($this->baseUrl, '/') . "/refresh/forecasts", [
            'query' => ['forecast_days' => $forecastDays],
            'timeout' => 120,
        ]);

        /** @var array<string, mixed> $out */
        $out = $res->toArray(false);
        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function trainForecastModel(int $lookbackDays = 365, int $holdoutDays = 30): array
    {
        $res = $this->http->request('POST', rtrim($this->baseUrl, '/') . "/train/forecast", [
            'query' => [
                'lookback_days' => $lookbackDays,
                'eval_holdout_days' => $holdoutDays,
            ],
            'timeout' => 300,
        ]);

        /** @var array<string, mixed> $out */
        $out = $res->toArray(false);
        return $out;
    }
}
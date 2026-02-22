<?php

namespace App\Entity;

use App\Repository\ProductForecastRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductForecastRepository::class)]
#[ORM\Table(name: 'product_forecast')]
#[ORM\UniqueConstraint(name: 'uniq_product_forecast', columns: ['product_id', 'forecast_days'])]
class ProductForecast
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column(name: 'forecast_days')]
    private int $forecastDays = 7;

    #[ORM\Column(name: 'predicted_qty', type: 'decimal', precision: 10, scale: 2)]
    private string $predictedQty = '0.00';

    // You can keep it in DB, but we will NOT trust it for UI status anymore
    #[ORM\Column(name: 'recommended_reorder_qty')]
    private int $recommendedReorderQty = 0;

    #[ORM\Column(name: 'generated_at', type: 'datetime')]
    private \DateTimeInterface $generatedAt;

    public function getId(): ?int { return $this->id; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }

    public function getForecastDays(): int { return $this->forecastDays; }
    public function setForecastDays(int $forecastDays): self { $this->forecastDays = $forecastDays; return $this; }

    public function getPredictedQty(): string { return $this->predictedQty; }
    public function setPredictedQty(string $predictedQty): self { $this->predictedQty = $predictedQty; return $this; }

    public function getRecommendedReorderQty(): int { return $this->recommendedReorderQty; }
    public function setRecommendedReorderQty(int $qty): self { $this->recommendedReorderQty = $qty; return $this; }

    public function getGeneratedAt(): \DateTimeInterface { return $this->generatedAt; }
    public function setGeneratedAt(\DateTimeInterface $dt): self { $this->generatedAt = $dt; return $this; }

    // ✅ helper (optional)
    public function getPredictedQtyFloat(): float
    {
        return (float) $this->predictedQty;
    }
}
<?php

namespace App\Entity;

use App\Repository\ProductRecommendationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'product_recommendation')]
#[ORM\UniqueConstraint(name: 'uniq_prod_rec', columns: ['product_id', 'recommended_product_id'])]
class ProductRecommendation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(name: 'recommended_product_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Product $recommendedProduct = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 6)]
    private string $score = '0.000000';

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $generatedAt;

    public function getId(): ?int { return $this->id; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }

    public function getRecommendedProduct(): ?Product { return $this->recommendedProduct; }
    public function setRecommendedProduct(Product $p): self { $this->recommendedProduct = $p; return $this; }

    public function getScore(): string { return $this->score; }
    public function setScore(string $score): self { $this->score = $score; return $this; }

    public function getGeneratedAt(): \DateTimeInterface { return $this->generatedAt; }
    public function setGeneratedAt(\DateTimeInterface $dt): self { $this->generatedAt = $dt; return $this; }
}
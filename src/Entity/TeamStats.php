<?php

namespace App\Entity;

use App\Repository\TeamStatsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamStatsRepository::class)]
#[ORM\Table(name: 'team_stats')]
class TeamStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Team::class, inversedBy: 'stats')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Team $team = null;

    #[ORM\Column]
    private int $totalWins = 0;

    #[ORM\Column]
    private int $totalLosses = 0;

    #[ORM\Column(type: 'float')]
    private float $eloRating = 1200.0; // Start all teams at 1200 (standard)

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;
        return $this;
    }

    public function getTotalWins(): int
    {
        return $this->totalWins;
    }

    public function setTotalWins(int $totalWins): static
    {
        $this->totalWins = $totalWins;
        return $this;
    }

    public function getTotalLosses(): int
    {
        return $this->totalLosses;
    }

    public function setTotalLosses(int $totalLosses): static
    {
        $this->totalLosses = $totalLosses;
        return $this;
    }

    public function getEloRating(): float
    {
        return $this->eloRating;
    }

    public function setEloRating(float $eloRating): static
    {
        $this->eloRating = $eloRating;
        return $this;
    }

    /**
     * Calculate win rate (0-1)
     */
    public function getWinRate(): float
    {
        $total = $this->totalWins + $this->totalLosses;
        if ($total === 0) {
            return 0;
        }
        return round($this->totalWins / $total, 2);
    }

    /**
     * Get total games played
     */
    public function getTotalGames(): int
    {
        return $this->totalWins + $this->totalLosses;
    }

    /**
     * Increment wins and update timestamp
     */
    public function incrementWins(): static
    {
        $this->totalWins++;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /**
     * Increment losses and update timestamp
     */
    public function incrementLosses(): static
    {
        $this->totalLosses++;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
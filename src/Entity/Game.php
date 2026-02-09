<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert; // ✨ This enables the validation rules

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The game name is required.")]
    #[Assert\Length(
        min: 2, 
        max: 255, 
        minMessage: "The game name must be at least {{ limit }} characters long.",
        maxMessage: "The game name cannot be longer than {{ limit }} characters."
    )]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The slug is required.")]
    #[Assert\Regex(
        pattern: '/^[a-z0-9-]+$/',
        message: "The slug can only contain lowercase letters, numbers, and dashes (e.g., 'elden-ring')."
    )]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        min: 10, 
        minMessage: "The description is too short. Please write at least {{ limit }} characters."
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?bool $hasRanking = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, Guide>
     */
    #[ORM\OneToMany(mappedBy: 'game', targetEntity: Guide::class, orphanRemoval: true)]
    private Collection $guides;

    public function __construct()
    {
        $this->guides = new ArrayCollection();
        // ✨ Set defaults here to prevent "null" errors
        $this->createdAt = new \DateTimeImmutable();
        $this->hasRanking = false; 
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    public function hasRanking(): ?bool
    {
        return $this->hasRanking;
    }

    public function setHasRanking(bool $hasRanking): static
    {
        $this->hasRanking = $hasRanking;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Guide>
     */
    public function getGuides(): Collection
    {
        return $this->guides;
    }

    public function addGuide(Guide $guide): static
    {
        if (!$this->guides->contains($guide)) {
            $this->guides->add($guide);
            $guide->setGame($this);
        }

        return $this;
    }

    public function removeGuide(Guide $guide): static
    {
        if ($this->guides->removeElement($guide)) {
            // set the owning side to null (unless already changed)
            if ($guide->getGame() === $this) {
                $guide->setGame(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        // This tells Symfony to use the Game's name in dropdown menus
        return $this->name; 
    }
}
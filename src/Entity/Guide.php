<?php

namespace App\Entity;

use App\Repository\GuideRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// ✨ Validation Component is already imported here
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GuideRepository::class)]
class Guide
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The title cannot be empty.")]
    #[Assert\Length(
        min: 5, 
        max: 255, 
        minMessage: "The title must be at least {{ limit }} characters long (e.g. 'Jett Guide').",
        maxMessage: "The title is too long."
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Please write a description.")]
    #[Assert\Length(
        min: 20, 
        minMessage: "Please provide a helpful description (at least {{ limit }} characters)."
    )]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Choice(
        choices: ['Easy', 'Medium', 'Hard'], 
        message: "Choose a valid difficulty: Easy, Medium, or Hard."
    )]
    private ?string $difficulty = null;

    #[ORM\ManyToOne(inversedBy: 'guides')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "You must select a game for this guide.")]
    private ?Game $game = null;

    #[ORM\ManyToOne(inversedBy: 'guides')]
    private ?User $author = null;

    /**
     * @var Collection<int, GuideStep>
     */
    #[ORM\OneToMany(mappedBy: 'guide', targetEntity: GuideStep::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Assert\Valid] // ✨ This validates the "Steps" inside the Guide form too!
    private Collection $guideSteps;

    /**
     * @var Collection<int, GuideRating>
     */
    #[ORM\OneToMany(mappedBy: 'guide', targetEntity: GuideRating::class, cascade: ['remove'])]
    private Collection $guideRatings;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cover_image = null;

    public function __construct()
    {
        $this->guideSteps = new ArrayCollection();
        $this->guideRatings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(?string $difficulty): static
    {
        $this->difficulty = $difficulty;
        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return Collection<int, GuideStep>
     */
    public function getGuideSteps(): Collection
    {
        return $this->guideSteps;
    }

    public function addGuideStep(GuideStep $guideStep): static
    {
        if (!$this->guideSteps->contains($guideStep)) {
            $this->guideSteps->add($guideStep);
            $guideStep->setGuide($this);

            // Auto-numbering logic
            if ($guideStep->getStepOrder() === null) {
                $guideStep->setStepOrder($this->guideSteps->count());
            }
        }

        return $this;
    }

    public function removeGuideStep(GuideStep $guideStep): static
    {
        if ($this->guideSteps->removeElement($guideStep)) {
            // set the owning side to null (unless already changed)
            if ($guideStep->getGuide() === $this) {
                $guideStep->setGuide(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GuideRating>
     */
    public function getGuideRatings(): Collection
    {
        return $this->guideRatings;
    }

    public function addGuideRating(GuideRating $guideRating): static
    {
        if (!$this->guideRatings->contains($guideRating)) {
            $this->guideRatings->add($guideRating);
            $guideRating->setGuide($this);
        }

        return $this;
    }

    public function removeGuideRating(GuideRating $guideRating): static
    {
        if ($this->guideRatings->removeElement($guideRating)) {
            if ($guideRating->getGuide() === $this) {
                $guideRating->setGuide(null);
            }
        }

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->cover_image;
    }

    public function setCoverImage(?string $cover_image): static
    {
        $this->cover_image = $cover_image;

        return $this;
    }
}
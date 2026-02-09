<?php

namespace App\Entity;

use App\Repository\GuideStepRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// ✨ Import Validator
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GuideStepRepository::class)]
class GuideStep
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "The step title is required.")]
    #[Assert\Length(
        min: 3, 
        max: 255, 
        minMessage: "Step title must be at least {{ limit }} characters long."
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Please write the content for this step.")]
    #[Assert\Length(
        min: 10, 
        minMessage: "Step content is too short. Please provide more details."
    )]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'guideSteps')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "This step must belong to a guide.")]
    private ?Guide $guide = null;

    #[ORM\Column]
    #[Assert\Positive(message: "Step order must be a positive number.")]
    private ?int $stepOrder = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(message: "The video URL must be a valid link (e.g., https://youtube.com/...).")]
    private ?string $video_url = null;

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getGuide(): ?Guide
    {
        return $this->guide;
    }

    public function setGuide(?Guide $guide): static
    {
        $this->guide = $guide;

        return $this;
    }

    public function getStepOrder(): ?int
    {
        return $this->stepOrder;
    }

    public function setStepOrder(int $stepOrder): static
    {
        $this->stepOrder = $stepOrder;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->video_url;
    }

    public function setVideoUrl(?string $video_url): static
    {
        $this->video_url = $video_url;

        return $this;
    }
}
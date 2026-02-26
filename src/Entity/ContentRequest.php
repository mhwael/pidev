<?php

namespace App\Entity;

use App\Repository\ContentRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContentRequestRepository::class)]
class ContentRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $userQuery = null;

    #[ORM\Column(length: 255)]
    private ?string $extractedKeywords = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserQuery(): ?string
    {
        return $this->userQuery;
    }

    public function setUserQuery(string $userQuery): static
    {
        $this->userQuery = $userQuery;

        return $this;
    }

    public function getExtractedKeywords(): ?string
    {
        return $this->extractedKeywords;
    }

    public function setExtractedKeywords(string $extractedKeywords): static
    {
        $this->extractedKeywords = $extractedKeywords;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}

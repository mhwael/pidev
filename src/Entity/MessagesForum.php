<?php

namespace App\Entity;

use App\Repository\MessagesForumRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MessagesForumRepository::class)]
class MessagesForum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messagesForum')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "Le sujet est obligatoire.")]
    private ?SujetsForum $sujetsForum = null;

    #[ORM\Column]
    #[Assert\NotNull(message: "")]
    #[Assert\Positive(message: "")]
    private ?int $auteur_id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "")]
    #[Assert\Length(min: 3, minMessage: "")]
    private ?string $contenu = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: "")]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\NotNull(message: "")]
    #[Assert\PositiveOrZero(message: "")]
    private int $nombre_likes = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSujetsForum(): ?SujetsForum
    {
        return $this->sujetsForum;
    }

    public function setSujetsForum(?SujetsForum $sujetsForum): static
    {
        $this->sujetsForum = $sujetsForum;
        return $this;
    }

    public function getAuteurId(): ?int
    {
        return $this->auteur_id;
    }

    // ✅ accepte null en entrée si tu veux, mais ici on garde int obligatoire
    public function setAuteurId(?int $auteur_id): static
    {
        $this->auteur_id = $auteur_id;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(?string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    // ✅ accepte null => pas de crash, la contrainte NotNull affiche l'erreur
    public function setDateCreation(?\DateTimeInterface $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getNombreLikes(): int
    {
        return $this->nombre_likes;
    }

    public function setNombreLikes(?int $nombre_likes): static
    {
        $this->nombre_likes = $nombre_likes ?? 0;
        return $this;
    }
}

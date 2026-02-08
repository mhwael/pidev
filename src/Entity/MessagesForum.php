<?php

namespace App\Entity;

use App\Repository\MessagesForumRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessagesForumRepository::class)]
class MessagesForum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messagesForum')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SujetsForum $sujetsForum = null;

    #[ORM\Column]
    private ?int $auteur_id = null;

    #[ORM\Column(length: 255)]
    private ?string $contenu = null;

    #[ORM\Column]
    private ?\DateTime $date_creation = null;

    #[ORM\Column]
    private ?int $nombre_likes = 0; // <-- nouvel attribut avec valeur par défaut 0

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

    public function setAuteurId(int $auteur_id): static
    {
        $this->auteur_id = $auteur_id;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTime $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getNombreLikes(): ?int
    {
        return $this->nombre_likes;
    }

    public function setNombreLikes(int $nombre_likes): static
    {
        $this->nombre_likes = $nombre_likes;
        return $this;
    }
}

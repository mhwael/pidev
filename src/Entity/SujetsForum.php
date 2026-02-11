<?php

namespace App\Entity;

use App\Repository\SujetsForumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SujetsForumRepository::class)]
class SujetsForum
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'sujetsForum', targetEntity: MessagesForum::class, orphanRemoval: true)]
    private Collection $messagesForum;

    public function __construct()
    {
        $this->messagesForum = new ArrayCollection();
    }

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "")]
    #[Assert\Length(min: 3, minMessage: "")]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: '')]
    #[Assert\Length(min: 3, minMessage: '')]
    private ?string $cree_par = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "")]
    private ?string $categorie = null;

    #[ORM\Column(type: 'datetime')]
    #[Assert\NotNull(message: "")]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: 'boolean')]
    private bool $est_verrouille = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getCreePar(): ?string
    {
        return $this->cree_par;
    }

    public function setCreePar(?string $cree_par): static
    {
        $this->cree_par = $cree_par;
        return $this;
    }

    public function getCategorie(): ?string
    {
        return $this->categorie;
    }

    public function setCategorie(?string $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    // ✅ accepte null => plus de crash, la validation PHP NotNull gère l'erreur
    public function setDateCreation(?\DateTimeInterface $date_creation): static
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getEstVerrouille(): bool
    {
        return $this->est_verrouille;
    }

    public function setEstVerrouille(bool $est_verrouille): static
    {
        $this->est_verrouille = $est_verrouille;
        return $this;
    }

    public function getMessagesForum(): Collection
    {
        return $this->messagesForum;
    }

    public function addMessagesForum(MessagesForum $messagesForum): static
    {
        if (!$this->messagesForum->contains($messagesForum)) {
            $this->messagesForum->add($messagesForum);
            $messagesForum->setSujetsForum($this);
        }
        return $this;
    }

    public function removeMessagesForum(MessagesForum $messagesForum): static
    {
        if ($this->messagesForum->removeElement($messagesForum)) {
            if ($messagesForum->getSujetsForum() === $this) {
                $messagesForum->setSujetsForum(null);
            }
        }
        return $this;
    }
}

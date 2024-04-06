<?php

namespace App\Entity;

use App\Repository\ArtistHasLabelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistHasLabelRepository::class)]
class ArtistHasLabel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'artistHasLabels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Label $id_label = null;

    #[ORM\ManyToOne(inversedBy: 'artistHasLabels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artist $idArtist = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdLabel(): ?Label
    {
        return $this->id_label;
    }

    public function setIdLabel(?Label $id_label): static
    {
        $this->id_label = $id_label;

        return $this;
    }

    public function getIdArtist(): ?Artist
    {
        return $this->idArtist;
    }

    public function setIdArtist(?Artist $idArtist): static
    {
        $this->idArtist = $idArtist;

        return $this;
    }
}

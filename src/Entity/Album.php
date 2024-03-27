<?php

namespace App\Entity;

use App\Repository\AlbumRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlbumRepository::class)]
class Album
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $idAlbum = null;

    #[ORM\Column(length: 90)]
    private ?string $Name = null;

    #[ORM\Column(length: 50)]
    private ?string $Category = null;

    #[ORM\Column(length: 135)]
    private ?string $Cover = null;

    #[ORM\Column]
    private ?int $Year = 2024;

    #[ORM\OneToMany(targetEntity: Song::class, mappedBy: 'Album_idAlbum')]
    private Collection $songs;

    public function __construct()
    {
        $this->songs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdAlbum(): ?int
    {
        return $this->idAlbum;
    }

    public function setIdAlbum(int $idAlbum): static
    {
        $this->idAlbum = $idAlbum;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): static
    {
        $this->Name = $Name;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->Category;
    }

    public function setCategory(string $Category): static
    {
        $this->Category = $Category;

        return $this;
    }

    public function getCover(): ?string
    {
        return $this->Cover;
    }

    public function setCover(string $Cover): static
    {
        $this->Cover = $Cover;

        return $this;
    }

    public function getYear(): ?int
    {
        return $this->Year;
    }

    public function setYear(int $Year): static
    {
        $this->Year = $Year;

        return $this;
    }

    /**
     * @return Collection<int, Song>
     */
    public function getSongs(): Collection
    {
        return $this->songs;
    }

    public function addSong(Song $song): static
    {
        if (!$this->songs->contains($song)) {
            $this->songs->add($song);
            $song->setAlbumIdAlbum($this);
        }

        return $this;
    }

    public function removeSong(Song $song): static
    {
        if ($this->songs->removeElement($song)) {
            // set the owning side to null (unless already changed)
            if ($song->getAlbumIdAlbum() === $this) {
                $song->setAlbumIdAlbum(null);
            }
        }

        return $this;
    }
}

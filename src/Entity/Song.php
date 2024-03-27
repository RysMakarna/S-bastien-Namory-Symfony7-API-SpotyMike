<?php

namespace App\Entity;

use App\Repository\SongRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SongRepository::class)]
class Song
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $idSong = null;

    #[ORM\Column(length: 45)]
    private ?string $Title = null;

    #[ORM\Column(length: 145)]
    private ?string $URL = null;

    #[ORM\Column(length: 125)]
    private ?string $Cover = null;

    #[ORM\Column]
    private ?bool $Visibility = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $CreatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'songs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Album $Album_idAlbum = null;

    #[ORM\ManyToMany(targetEntity: Artist::class, inversedBy: 'songs')]
    private Collection $Artist_idArtist;

    public function __construct()
    {
        $this->Artist_idArtist = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdSong(): ?string
    {
        return $this->idSong;
    }

    public function setIdSong(string $idSong): static
    {
        $this->idSong = $idSong;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->Title;
    }

    public function setTitle(string $Title): static
    {
        $this->Title = $Title;

        return $this;
    }

    public function getURL(): ?string
    {
        return $this->URL;
    }

    public function setURL(string $URL): static
    {
        $this->URL = $URL;

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

    public function isVisibility(): ?bool
    {
        return $this->Visibility;
    }

    public function setVisibility(bool $Visibility): static
    {
        $this->Visibility = $Visibility;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->CreatedAt;
    }

    public function setCreatedAt(\DateTimeImmutable $CreatedAt): static
    {
        $this->CreatedAt = $CreatedAt;

        return $this;
    }

    public function getAlbumIdAlbum(): ?Album
    {
        return $this->Album_idAlbum;
    }

    public function setAlbumIdAlbum(?Album $Album_idAlbum): static
    {
        $this->Album_idAlbum = $Album_idAlbum;

        return $this;
    }

    /**
     * @return Collection<int, Artist>
     */
    public function getArtistIdArtist(): Collection
    {
        return $this->Artist_idArtist;
    }

    public function addArtistIdArtist(Artist $artistIdArtist): static
    {
        if (!$this->Artist_idArtist->contains($artistIdArtist)) {
            $this->Artist_idArtist->add($artistIdArtist);
        }

        return $this;
    }

    public function removeArtistIdArtist(Artist $artistIdArtist): static
    {
        $this->Artist_idArtist->removeElement($artistIdArtist);

        return $this;
    }
}

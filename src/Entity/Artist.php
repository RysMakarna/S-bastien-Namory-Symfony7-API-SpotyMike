<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    
    #[ORM\OneToOne(inversedBy: 'artist', cascade: ['remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $User_idUser = null;

    #[ORM\Column(length: 90,unique:true)]
    private ?string $fullname = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $actif = 1;

    #[ORM\ManyToMany(targetEntity: Song::class, mappedBy: 'Artist_idUser')]
    private Collection $songs;

    #[ORM\OneToMany(targetEntity: Album::class, mappedBy: 'artist_User_idUser')]
    private Collection $albums;
    private ?User $user = null;

    #[ORM\OneToMany(targetEntity: ArtistHasLabel::class, mappedBy: 'User_idUser')]
    private Collection $artistHasLabels;
    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updateAt = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'following')]
    private Collection $follower;

    public function __construct()
    {
        $this->songs = new ArrayCollection();
        $this->albums = new ArrayCollection();
        $this->follower = new ArrayCollection();
    }
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function getUserIdUser(): ?User
    {
        return $this->User_idUser;
    }

    public function setUserIdUser(User $User_idUser): static
    {
        $this->User_idUser = $User_idUser;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): static
    {
        $this->fullname = $fullname;

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

    public function getActif(): ?int
    {
        return $this->actif;
    }

    public function setActif(?int $actif): static
    {
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
            $song->addArtistIdUser($this);
        }

        return $this;
    }
    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeInterface $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }
    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;

        return $this;
    }

    public function removeSong(Song $song): static
    {
        if ($this->songs->removeElement($song)) {
            $song->removeArtistIdUser($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Album>
     */
    public function getAlbums(): Collection
    {
        return $this->albums;
    }

    public function addAlbum(Album $album): static
    {
        if (!$this->albums->contains($album)) {
            $this->albums->add($album);
            $album->setArtistUserIdUser($this);
        }

        return $this;
    }

    public function removeAlbum(Album $album): static
    {
        if ($this->albums->removeElement($album)) {
            // set the owning side to null (unless already changed)
            if ($album->getArtistUserIdUser() === $this) {
                $album->setArtistUserIdUser(null);
            }
        }

        return $this;
    }
    public function ArtistSerealizer($name)
    {
        $sexe = $this->getUserIdUser()->getSexe() === "0" ? 'Homme' : ($this->getUserIdUser()->getSexe() === "1" ? 'Femme' : ($this->getUserIdUser()->getSexe() === "2" ? 'Non-Binaire': null));

        return [
            "firstname" =>  $this->getUserIdUser()->getFirstName(),
            "lastname" => $this->getUserIdUser()->getLastname(),
            "email" => $this->getUserIdUser()->getEmail(),
            "tel" => $this->getUserIdUser()->getTel(),
            "sexe" => $sexe,
            "dateBirth" => $this->getUserIdUser()->getBirthday()->format('d-m-Y'), // Will need to be in format('d-m-Y'),
            "Artist.createdAt" => $this->getCreateAt()->format('Y-m-d'),
            "Albums"=>$this->serializerInformation($name),
        ];
    }
    public function serializer(){
        if($this->getActif()==0){
            return null;
        }
        return [
            "fullname" => $this->getFullname(),
            "description" => $this->getDescription(),
        ];
    }
    

    public function serializerUser(){
        return [
            "idUser" => ($children) ? $this->getUserIdUser() : null,
            "fistname" => $this->getUserIdUser()->getFirstName(),
            "lastanme" => $this->getUserIdUser()->getLastname(),
        ];
    }

    public function serializerInformation($name){
        // Vérifier si l'objet est actif
    if ($this->getActif() == 0) {
        return null;
    }
    $albums = $this->getAlbums();
    if ($albums === null) {
        return [];
    }
    $serializedAlbums = [];
    foreach ($albums as $album) {
        $serializedAlbums[] = $album->serializer($name);
    }
    

    return $serializedAlbums;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFollower(): Collection
    {
        return $this->follower;
    }
    public function addArtistHasLabel(ArtistHasLabel $artistHasLabel): static
    {
        if (!$this->artistHasLabels->contains($artistHasLabel)) {
            $this->artistHasLabels->add($artistHasLabel);
            $artistHasLabel->setIdArtist($this);
        }

        return $this;
    }

    public function removeArtistHasLabel(ArtistHasLabel $artistHasLabel): static
    {
        if ($this->artistHasLabels->removeElement($artistHasLabel)) {
            // set the owning side to null (unless already changed)
            if ($artistHasLabel->getIdArtist() === $this) {
                $artistHasLabel->setIdArtist(null);
            }
        }

        return $this;
    }

    public function addFollower(User $follower): static
    {
        if (!$this->follower->contains($follower)) {
            $this->follower->add($follower);
            $follower->addFollowing($this);
        }

        return $this;
    }

    public function removeFollower(User $follower): static
    {
        if ($this->follower->removeElement($follower)) {
            $follower->removeFollowing($this);
        }

        return $this;
    }
}

<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
//use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    // #[ORM\Id]
    #[ORM\Column(length: 90, unique: true)]
    private ?string $idUser = null;

    #[ORM\Column(length: 55)]
    private ?string $firstname = null;

    #[ORM\Column(length: 55)]
    private ?string $lastname = null;

    #[ORM\Column(length: 80, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $tel = null;
    #[ORM\Column(length: 20)] // Default Value : "Non Précisé" -> Ne sera pas montré à d'autres utilisateurs
    private ?string $sexe = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)] // Sera changé PAR le controller
    private ?\DateTimeInterface $birthday = null;

    #[ORM\Column(length: 90)]
    private ?string $password = null;
    #[ORM\Column]
    private ?int $nbTentative = 0;


    #[ORM\Column]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updateAt = null;

    #[ORM\OneToOne(mappedBy: 'User_idUser', cascade: ['persist', 'remove'])]
    private ?Artist $artist = null;

    #[ORM\Column]
    private ?bool $actif = null;

    #[ORM\Column]
    private ?int $reset_password = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateRest = null;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdUser(): ?string
    {
        return $this->idUser;
    }
    public function getnbTentative(): ?string
    {
        return $this->nbTentative;
    }
    public function setIdUser(string $idUser): static
    {
        $this->idUser = $idUser;

        return $this;
    }
    public function setnbTentative(int $nbTentative): static
    {
        $this->nbTentative = $nbTentative;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(string $sexe): static
    {
        $this->sexe = $sexe;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(\DateTimeInterface $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): static
    {
        $this->tel = $tel;

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

    public function getUpdateAt(): ?\DateTimeInterface
    {
        return $this->updateAt;
    }

    public function setUpdateAt(\DateTimeInterface $updateAt): static
    {
        $this->updateAt = $updateAt;

        return $this;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(Artist $artist): static
    {
        // set the owning side of the relation if necessary
        if ($artist->getUserIdUser() !== $this) {
            $artist->setUserIdUser($this);
        }

        $this->artist = $artist;

        return $this;
    }
    public function getRoles(): array
    {

        return [];
    }

    public function eraseCredentials(): void
    {

    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
    public function UserSerializer()
    {
        return [
            "firstname" => $this->getFirstname(),
            "lastname" => $this->getLastname(),
            "email" => $this->getEmail(),
            "tel" => $this->getTel(),
            "sexe" => $this->getSexe(),
            "artist" => $this->getArtist() ? $this->getArtist()->serializer() : [],
            "dateBirth" => $this->getBirthday(), // Will need to be in format('d-m-Y'),
            "createAt" => $this->getCreateAt(),
        ];
    }
    public function Serializer()
    {
        $dateOfBirth = $this->getBirthday();

        // Formater la date de naissance au format 'd-m-Y' si elle est disponible

        return [
            "idUser" => $this->getIdUser(),
            "firstname" => $this->getFirstname(),
            "lastname" => $this->getLastname(),
            "email" => $this->getEmail(),
            "dateBirth" => $this->getBirthday()->format('d-m-Y'), // Will need to be in format('d-m-Y'),
            "Artist.createAt" => $this->getCreateAt()->format('d-m-Y'),
        ];
    }
    public function UserSerialRegis($artist)
    {
        return [
            "firstname" => $this->getFirstname(),
            "lastname" => $this->getLastname(),
            "email" => $this->getEmail(),
            "tel" => $this->getTel(),
            "sexe" => $this->getSexe(),
            "artist"=>$artist ?$artist->serializer() :null,
            "dateBirth" => $this->getBirthday()->format('d-m-Y'), // Will need to be in format('d-m-Y'),
            "createAt" => $this->getCreateAt()->format('d-m-Y'),
        ];
    }
    public function UserSerial()
    {
        return [
            "firstname" => $this->getFirstname(),
            "lastname" => $this->getLastname(),
            "email" => $this->getEmail(),
            "tel" => $this->getTel(),
            "sexe" => $this->getSexe(),
            "dateBirth" => $this->getBirthday()->format('d-m-Y'), // Will need to be in format('d-m-Y'),
            "createAt" => $this->getCreateAt()->format('d-m-Y'),
        ];
    }

    public function isActif(): ?bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getResetPassword(): ?int
    {
        return $this->reset_password;
    }

    public function setResetPassword(?int $reset_password): static
    {
        $this->reset_password = $reset_password;

        return $this;
    }

    public function getDateRest(): ?\DateTimeInterface
    {
        return $this->dateRest;
    }

    public function setDateRest(?\DateTimeInterface $dateRest): static
    {
        $this->dateRest = $dateRest;

        return $this;
    }
}
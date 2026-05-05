<?php

namespace App\Entity;

use App\Repository\PlayerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom du joueur est obligatoire.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom du joueur est obligatoire.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: "L'adresse email du joueur n'est pas valide.")]
    private ?string $email = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: "L'adresse email du responsable légal n'est pas valide")]
    private ?string $guardianEmail = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $guardianPhone = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $position = null;

    // validation du numéro du maillot entre 1 et 99
    #[ORM\Column(nullable: true)]
    #[Assert\Range(
        min: 1,
        max: 99,
        notInRangeMessage: 'Le numéro de maillot doit être compris entre {{ min }} et {{ max }}.'
    )]
    private ?int $jerseyNumber = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le statut du joueur est obligatoire.')]
    #[Assert\Choice(
        choices: ['active', 'injured', 'suspended', 'inactive'],
        message: 'Le statut du joueur est invalide.')]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'players')]
    private ?Team $team = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status = 'active';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    // Retourne le nom complet du joueur.
    // Exemple : Kylian Mbappé
    public function getFullName(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getGuardianEmail(): ?string
    {
        return $this->guardianEmail;
    }

    public function setGuardianEmail(?string $guardianEmail): static
    {
        $this->guardianEmail = $guardianEmail;

        return $this;
    }

    public function getGuardianPhone(): ?string
    {
        return $this->guardianPhone;
    }

    public function setGuardianPhone(?string $guardianPhone): static
    {
        $this->guardianPhone = $guardianPhone;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getJerseyNumber(): ?int
    {
        return $this->jerseyNumber;
    }

    public function setJerseyNumber(?int $jerseyNumber): static
    {
        $this->jerseyNumber = $jerseyNumber;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }


    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    // Si le joueur n'a pas d'email OU pas de téléphone alors il faut renseigner l'email ET le téléphone du responsable légal
    #[Assert\Callback]
    public function validateContactInformation(ExecutionContextInterface $context): void
    {
        $playerEmailMissing = empty($this->email);
        $playerPhoneMissing = empty($this->phone);

        if ($playerEmailMissing || $playerPhoneMissing) {
            if (empty($this->guardianEmail)) {
                $context->buildViolation('L’email du parent / responsable légal est obligatoire si les coordonnées du joueur sont incomplètes.')
                    ->atPath('guardianEmail')
                    ->addViolation();
            }

            if (empty($this->guardianPhone)) {
                $context->buildViolation('Le téléphone du parent / responsable légal est obligatoire si les coordonnées du joueur sont incomplètes.')
                    ->atPath('guardianPhone')
                    ->addViolation();
            }
        }
    }
}

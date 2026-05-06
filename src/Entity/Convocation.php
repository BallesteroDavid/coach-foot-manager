<?php

namespace App\Entity;

use App\Repository\ConvocationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConvocationRepository::class)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_CONVOCATION_MATCH_PLAYER',
    columns: ['football_match_id', 'player_id']
)]
class Convocation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'convocations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le match est obligatoire.')]
    private ?FootballMatch $footballMatch = null;

    #[ORM\ManyToOne(inversedBy: 'convocations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le joueur est obligatoire.')]
    private ?Player $player = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le statut de la convocation est obligatoire.')]
    #[Assert\Choice(
        choices: ['called', 'present', 'absent', 'excused'],
        message: 'Le statut de la convocation est invalide.'
    )]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'Le commentaire ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $comment = null;

    #[ORM\ManyToOne(inversedBy: 'createdConvocations')]
    private ?AppUser $createdBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        // Date de création automatiquement renseignée à la création de la convocation.
        $this->createdAt = new \DateTimeImmutable();

        // Par défaut, un joueur ajouté à une convocation est simplement convoqué.
        $this->status = 'called';
    }

    public function __toString(): string
    {
        $playerName = $this->player?->getFullName() ?? 'Joueur inconnu';
        $matchLabel = $this->footballMatch?->getOpponent() ?? 'Match inconnu';

        return $playerName . ' - ' . $matchLabel;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'called' => 'Convoqué',
            'present' => 'Présent',
            'absent' => 'Absent',
            'excused' => 'Excusé',
            default => 'Statut inconnu',
        };
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFootballMatch(): ?FootballMatch
    {
        return $this->footballMatch;
    }

    public function setFootballMatch(?FootballMatch $footballMatch): static
    {
        $this->footballMatch = $footballMatch;

        return $this;
    }

    public function getPlayer(): ?Player
    {
        return $this->player;
    }

    public function setPlayer(?Player $player): static
    {
        $this->player = $player;

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

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedBy(): ?AppUser
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?AppUser $createdBy): static
    {
        $this->createdBy = $createdBy;

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
}
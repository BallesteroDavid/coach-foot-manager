<?php

namespace App\Entity;

use App\Repository\TrainingAttendanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrainingAttendanceRepository::class)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_TRAINING_ATTENDANCE_SESSION_PLAYER',
    columns: ['training_session_id', 'player_id']
)]
class TrainingAttendance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'attendances')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "La séance d'entraînement est obligatoire.")]
    private ?TrainingSession $trainingSession = null;

    #[ORM\ManyToOne(inversedBy: 'trainingAttendances')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le joueur est obligatoire.')]
    private ?Player $player = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le statut de présence est obligatoire.')]
    #[Assert\Choice(
        choices: ['present', 'absent', 'excused', 'late', 'injured', 'exempt'],
        message: 'Le statut de présence est invalide.'
    )]
    private ?string $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'Le commentaire ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $comment = null;

    #[ORM\ManyToOne(inversedBy: 'updatedTrainingAttendances')]
    private ?AppUser $updatedBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        // Date de création automatiquement renseignée à la création de la présence.
        $this->createdAt = new \DateTimeImmutable();

        // Par défaut, un joueur ajouté à une feuille de présence est marqué présent.
        $this->status = 'present';
    }

    public function __toString(): string
    {
        $playerName = $this->player?->getFullName() ?? 'Joueur inconnu';
        $trainingLabel = $this->trainingSession?->__toString() ?? 'Entraînement inconnu';

        return $playerName . ' - ' . $trainingLabel;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'present' => 'Présent',
            'absent' => 'Absent',
            'excused' => 'Excusé',
            'late' => 'En retard',
            'injured' => 'Blessé',
            'exempt' => 'Dispensé',
            default => 'Statut inconnu',
        };
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrainingSession(): ?TrainingSession
    {
        return $this->trainingSession;
    }

    public function setTrainingSession(?TrainingSession $trainingSession): static
    {
        $this->trainingSession = $trainingSession;

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

    public function getUpdatedBy(): ?AppUser
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?AppUser $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

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
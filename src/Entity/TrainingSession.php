<?php

namespace App\Entity;

use App\Repository\TrainingSessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: TrainingSessionRepository::class)]
class TrainingSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trainingSessions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'équipe de l'entraînement est obligatoire.")]
    private ?Team $team = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: "La date de l'entraînement est obligatoire.")]
    private ?\DateTimeImmutable $trainingDate = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Assert\NotNull(message: "L'heure de début est obligatoire.")]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Assert\NotNull(message: "L'heure de fin est obligatoire.")]
    private ?\DateTimeImmutable $endTime = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le lieu de l'entraînement est obligatoire.")]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le lieu ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Le thème ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $theme = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'Le commentaire ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $comment = null;

    #[ORM\ManyToOne(inversedBy: 'createdTrainingSessions')]
    private ?AppUser $createdBy = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, TrainingAttendance>
     */
    #[ORM\OneToMany(targetEntity: TrainingAttendance::class, mappedBy: 'trainingSession')]
    private Collection $attendances;

    public function __construct()
    {
        // Date de création automatiquement renseignée à la création de l'entraînement.
        $this->createdAt = new \DateTimeImmutable();
        $this->attendances = new ArrayCollection();
    }

    public function __toString(): string
    {
        $teamName = $this->team?->getName() ?? 'Équipe inconnue';
        $date = $this->trainingDate?->format('d/m/Y') ?? 'Date inconnue';

        return $teamName . ' - ' . $date;
    }

    #[Assert\Callback]
    public function validateTrainingTimes(ExecutionContextInterface $context): void
    {
        if (
            $this->startTime !== null
            && $this->endTime !== null
            && $this->endTime <= $this->startTime
        ) {
            $context->buildViolation("L'heure de fin doit être après l'heure de début.")
                ->atPath('endTime')
                ->addViolation();
        }
    }

    public function getDurationLabel(): string
    {
        if ($this->startTime === null || $this->endTime === null) {
            return 'Durée non renseignée';
        }

        $startMinutes = ((int) $this->startTime->format('H')) * 60 + (int) $this->startTime->format('i');
        $endMinutes = ((int) $this->endTime->format('H')) * 60 + (int) $this->endTime->format('i');

        if ($endMinutes <= $startMinutes) {
            return 'Durée invalide';
        }

        $duration = $endMinutes - $startMinutes;

        $hours = intdiv($duration, 60);
        $minutes = $duration % 60;

        if ($hours > 0 && $minutes > 0) {
            return $hours . 'h' . str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
        }

        if ($hours > 0) {
            return $hours . 'h';
        }

        return $minutes . ' min';
    }

    public function getTimeRangeLabel(): string
    {
        if ($this->startTime === null || $this->endTime === null) {
            return 'Horaires non renseignés';
        }

        return $this->startTime->format('H:i') . ' - ' . $this->endTime->format('H:i');
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTrainingDate(): ?\DateTimeImmutable
    {
        return $this->trainingDate;
    }

    public function setTrainingDate(\DateTimeImmutable $trainingDate): static
    {
        $this->trainingDate = $trainingDate;

        return $this;
    }

    public function getStartTime(): ?\DateTimeImmutable
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeImmutable $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeImmutable
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeImmutable $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

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

    /**
     * @return Collection<int, TrainingAttendance>
     */
    public function getAttendances(): Collection
    {
        return $this->attendances;
    }

    public function getAttendancesCount(): int
    {
        return $this->attendances->count();
    }

    public function getPresentAttendancesCount(): int
    {
        return $this->attendances
            ->filter(fn (TrainingAttendance $attendance) => $attendance->getStatus() === 'present')
            ->count();
    }

    public function addAttendance(TrainingAttendance $attendance): static
    {
        if (!$this->attendances->contains($attendance)) {
            $this->attendances->add($attendance);
            $attendance->setTrainingSession($this);
        }

        return $this;
    }

    public function removeAttendance(TrainingAttendance $attendance): static
    {
        if ($this->attendances->removeElement($attendance)) {
            // set the owning side to null (unless already changed)
            if ($attendance->getTrainingSession() === $this) {
                $attendance->setTrainingSession(null);
            }
        }

        return $this;
    }
}
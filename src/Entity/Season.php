<?php

namespace App\Entity;

use App\Repository\SeasonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: SeasonRepository::class)]
class Season
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom de la saison est obligatoire.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le nom de la saison ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'La date de début de saison est obligatoire.')]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'La date de fin de saison est obligatoire.')]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le statut de la saison est obligatoire.')]
    #[Assert\Choice(
        choices: ['planned', 'active', 'closed'],
        message: 'Le statut de la saison est invalide.'
    )]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'seasons')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le club est obligatoire.')]
    private ?Club $club = null;

    /**
     * @var Collection<int, Team>
     */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'season')]
    private Collection $teams;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        // Date de création automatiquement renseignée à la création de la saison.
        $this->createdAt = new \DateTimeImmutable();

        // Par défaut, une nouvelle saison est considérée comme planifiée.
        $this->status = 'planned';

        // Initialisation obligatoire de la collection des équipes.
        $this->teams = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getTeamsCount(): int
    {
        return $this->teams->count();
    }

    // Affiche une période lisible.
    // Exemple : 01/07/2025 - 30/06/2026
    public function getPeriodLabel(): string
    {
        if ($this->startDate === null || $this->endDate === null) {
            return 'Période non renseignée';
        }

        return $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y');
    }

    #[Assert\Callback]
    public function validateSeasonDates(ExecutionContextInterface $context): void
    {
        // La date de début doit être avant la date de fin.
        if (
            $this->startDate !== null
            && $this->endDate !== null
            && $this->startDate > $this->endDate
        ) {
            $context->buildViolation('La date de début ne peut pas être après la date de fin.')
                ->atPath('startDate')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

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

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(?Club $club): static
    {
        $this->club = $club;

        return $this;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): static
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setSeason($this);
        }

        return $this;
    }

    public function removeTeam(Team $team): static
    {
        if ($this->teams->removeElement($team)) {
            if ($team->getSeason() === $this) {
                $team->setSeason(null);
            }
        }

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
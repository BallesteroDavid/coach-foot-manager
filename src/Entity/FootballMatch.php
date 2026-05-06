<?php

namespace App\Entity;

use App\Repository\FootballMatchRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: FootballMatchRepository::class)]
class FootballMatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'footballMatches')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: "L'équipe du match est obligatoire.")]
    private ?Team $team = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'La date du match est obligatoire.')]
    private ?\DateTimeImmutable $matchDate = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE)]
    #[Assert\NotNull(message: "L'heure du match est obligatoire.")]
    private ?\DateTimeImmutable $startTime = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le lieu du match est obligatoire.')]
    private ?string $location = null;

    #[ORM\Column(length: 30)]
    #[Assert\NotBlank(message: 'Le type de lieu est obligatoire.')]
    #[Assert\Choice(
        choices: ['home', 'away', 'neutral'],
        message: 'Le type de lieu doit être : domicile, extérieur ou neutre.'
    )]
    private ?string $locationType = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'adversaire est obligatoire.")]
    private ?string $opponent = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $competition = null;

    // Score de l'équipe à domicile
    // Nullable car le score n'est pas connu avant ou pendant la création du match
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le score ne peut pas être négatif.')]
    private ?int $homeScore = null;

    // Score de l'équipe à l'exterieur
    // Nullable pour la même raison que homeScore
    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero(message: 'Le score ne peut pas être négatif.')]
    private ?int $awayScore = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(
        choices: ['planned', 'in_progress', 'finished', 'cancelled'],
        message: 'Le statut du match est invalide.'
    )]
    private ?string $status = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // Type de match :
    // - simple : match classique sans match retour
    // - aller : premier match d'une double confrontation
    // - retour : match retour à un match aller
    #[ORM\Column(length: 30)]
    #[Assert\Choice(
        choices: ['simple', 'aller', 'retour'],
        message: 'Le type de match est invalide.'
    )]
    private ?string $matchType = null;

    // Si ce match est un match retour, il peut être lié à un match aller
    // Exemple : PSG - Lyon peut être le retour de Lyon - PSG
    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'returnMatches')]
    private ?self $firstMatch = null;

    /**
     * Liste des matchs retour liés à ce match
     * Cette propriété ne crée pas une colonne "return_matches" en bdd
     * Doctrine reconstruit cette liste grâce à la colonne "first_match_id" présente sur les matchs retour
     * 
     * @var Collection<int, self>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'firstMatch')]
    private Collection $returnMatches;

    /**
     * @var Collection<int, Convocation>
     */
    #[ORM\OneToMany(targetEntity: Convocation::class, mappedBy: 'footballMatch')]
    private Collection $convocations;

    public function __construct()
    {
        // date de création automatiquement renseignée à la création d'un match
        $this->createdAt = new \DateTimeImmutable();
        // par défaut, un match nouvellement créé est considéré comme planifié
        $this->status = 'planned';
        // Par défaut, un match est simple tant qu'on ne précise pas qu'il est aller ou retour
        $this->matchType = 'simple';
        // Initialisation obligatoire de la collection pour éviter une erreur
        $this->returnMatches = new ArrayCollection();
        $this->convocations = new ArrayCollection();
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

    public function getMatchDate(): ?\DateTimeImmutable
    {
        return $this->matchDate;
    }

    public function setMatchDate(\DateTimeImmutable $matchDate): static
    {
        $this->matchDate = $matchDate;

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

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(string $location): static
    {
        $this->location = $location;

        return $this;
    }

    public function getLocationType(): ?string
    {
        return $this->locationType;
    }

    public function setLocationType(string $locationType): static
    {
        $this->locationType = $locationType;

        return $this;
    }

    public function getOpponent(): ?string
    {
        return $this->opponent;
    }

    public function setOpponent(string $opponent): static
    {
        $this->opponent = $opponent;

        return $this;
    }

    public function getCompetition(): ?string
    {
        return $this->competition;
    }

    public function setCompetition(?string $competition): static
    {
        $this->competition = $competition;

        return $this;
    }

    public function getHomeScore(): ?int
    {
        return $this->homeScore;
    }

    public function setHomeScore(?int $homeScore): static
    {
        $this->homeScore = $homeScore;

        return $this;
    }

    public function getAwayScore(): ?int
    {
        return $this->awayScore;
    }

    public function setAwayScore(?int $awayScore): static
    {
        $this->awayScore = $awayScore;

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

    // Retourne un affichage du score, par exemple : 2 - 1
    // si un des deux score n'est pas renseigné, on affiche un message par défaut
    public function getScoreLabel(): string
    {
        if ($this->homeScore === null || $this->awayScore === null) {
            return 'Score non renseigné';
        }

        return $this->homeScore . ' - ' . $this->awayScore;
    }

    // Retourne le nombre total de buts du match
    // Si le score n'est pas complet, on retourne null
    public function getTotalGoals(): ?int
    {
        if ($this->homeScore === null || $this->awayScore === null) {
            return null;
        }

        return $this->homeScore + $this->awayScore;
    }

    // Retourne le score de l'équipe du club.
    // Si le club joue à domicile, on prend homeScore.
    // Si le club joue à l'extérieur, on prend awayScore.
    // Si le match est sur terrain neutre, on considère par convention
    // que l'équipe du club correspond au score domicile.
    public function getTeamScore(): ?int
    {
        if ($this->homeScore === null || $this->awayScore === null) {
            return null;
        }

        return match ($this->locationType) {
            'away' => $this->awayScore,
            default => $this->homeScore,
        };
    }

    // Retourne le score de l'adversaire.
    public function getOpponentScore(): ?int
    {
        if ($this->homeScore === null || $this->awayScore === null) {
            return null;
        }

        return match ($this->locationType) {
            'away' => $this->homeScore,
            default => $this->awayScore,
        };
    }

    // Retourne le résultat du match du point de vue de l'équipe du club.
    // Exemple : Victoire, Défaite, Match nul, Non joué.
    public function getResultLabel(): string
    {
        if ($this->status === 'cancelled') {
            return 'Match annulé';
        }

        if ($this->homeScore === null || $this->awayScore === null) {
            return 'Non joué';
        }

        $teamScore = $this->getTeamScore();
        $opponentScore = $this->getOpponentScore();

        if ($teamScore > $opponentScore) {
            return 'Victoire';
        }

        if ($teamScore < $opponentScore) {
            return 'Défaite';
        }

        return 'Match nul';
    }

    #[Assert\Callback]
    public function validateMatchType(ExecutionContextInterface $context): void
    {
        // Un match retour doit obligatoirement être lié à un match aller.
        if ($this->matchType === 'retour' && $this->firstMatch === null) {
            $context->buildViolation('Un match retour doit être lié à un match aller.')
                ->atPath('firstMatch')
                ->addViolation();
        }

        // Un match simple ou un match aller ne doit pas avoir de match aller associé.
        if ($this->matchType !== 'retour' && $this->firstMatch !== null) {
            $context->buildViolation('Seul un match retour peut être lié à un match aller.')
                ->atPath('firstMatch')
                ->addViolation();
        }

        // Sécurité : un match ne peut pas être lié à lui-même.
        if ($this->firstMatch === $this) {
            $context->buildViolation('Un match ne peut pas être lié à lui-même.')
                ->atPath('firstMatch')
                ->addViolation();
        }

        // Si un match retour est lié à un match, ce match doit être de type "aller".
        if (
            $this->matchType === 'retour'
            && $this->firstMatch !== null
            && $this->firstMatch->getMatchType() !== 'aller'
        ) {
            $context->buildViolation('Un match retour doit être lié à un match de type aller.')
                ->atPath('firstMatch')
                ->addViolation();
        }

        // Si ce match possède déjà un ou plusieurs matchs retour,
        // il doit rester de type "aller".
        if ($this->returnMatches->count() > 0 && $this->matchType !== 'aller') {
            $context->buildViolation('Un match qui possède un match retour associé doit rester de type aller.')
                ->atPath('matchType')
                ->addViolation();
        }

        // Si le match est terminé, le score domicile doit être renseigné.
        if ($this->status === 'finished' && $this->homeScore === null) {
            $context->buildViolation('Le score domicile est obligatoire pour un match terminé.')
                ->atPath('homeScore')
                ->addViolation();
        }

        // Si le match est terminé, le score extérieur doit être renseigné.
        if ($this->status === 'finished' && $this->awayScore === null) {
            $context->buildViolation('Le score extérieur est obligatoire pour un match terminé.')
                ->atPath('awayScore')
                ->addViolation();
        }
    }

    public function getMatchType(): ?string
    {
        return $this->matchType;
    }

    public function setMatchType(string $matchType): static
    {
        $this->matchType = $matchType;

        return $this;
    }

    public function getFirstMatch(): ?self
    {
        return $this->firstMatch;
    }

    public function setFirstMatch(?self $firstMatch): static
    {
        $this->firstMatch = $firstMatch;

        return $this;
    }

    /**
     * Retourne la liste des matchs retour liés à ce match
     * Exemple : 
     * le match aller Lyon vs PSG peut avoir comme match retour PSG vs Lyon
     * @return Collection<int, self>
     */
    public function getReturnMatches(): Collection
    {
        return $this->returnMatches;
    }

    // Ajoute un match retour à ce match
    // cette méthode met aussi à jour l'autre coté de la relation avec setFirstMatch()
    public function addReturnMatch(self $returnMatch): static
    {
        if (!$this->returnMatches->contains($returnMatch)) {
            $this->returnMatches->add($returnMatch);

            // On indique que le match ajouté est un retour de ce match
            $returnMatch->setFirstMatch($this);
        }

        return $this;
    }

    // retire un match retour de ce match
    // si le match retour était lié à ce match, on supprime aussi le lien côté "firstMatch"
    public function removeReturnMatch(self $returnMatch): static
    {
        if ($this->returnMatches->removeElement($returnMatch)) {
            // On évite de supprimer le lien si le match retour a déjà été rattaché à un autre match
            if ($returnMatch->getFirstMatch() === $this) {
                $returnMatch->setFirstMatch(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Convocation>
     */
    public function getConvocations(): Collection
    {
        return $this->convocations;
    }

    public function getConvocationsCount(): int
    {
        return $this->convocations->count();
    }

    public function addConvocation(Convocation $convocation): static
    {
        if (!$this->convocations->contains($convocation)) {
            $this->convocations->add($convocation);
            $convocation->setFootballMatch($this);
        }

        return $this;
    }

    public function removeConvocation(Convocation $convocation): static
    {
        if ($this->convocations->removeElement($convocation)) {
            // set the owning side to null (unless already changed)
            if ($convocation->getFootballMatch() === $this) {
                $convocation->setFootballMatch(null);
            }
        }

        return $this;
    }
}

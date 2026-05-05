<?php

namespace App\Entity;

use App\Repository\TeamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TeamRepository::class)]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom de l'équipe est obligatoire.")]
    #[Assert\Length(
        max: 100,
        maxMessage: "Le nom de l'équipe ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 1000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'teams')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le club est obligatoire.')]
    private ?Club $club = null;

    /**
     * @var Collection<int, Player>
     */
    #[ORM\OneToMany(targetEntity: Player::class, mappedBy: 'team')]
    private Collection $players;

    /**
     * @var Collection<int, FootballMatch>
     */
    #[ORM\OneToMany(targetEntity: FootballMatch::class, mappedBy: 'team')]
    private Collection $footballMatches;

    public function __construct()
    {
        // date de création automatiquement renseignée à la création de l'équipe
        $this->createdAt = new \DateTimeImmutable();

        // Initialisation des collections liées à l'équipe
        $this->players = new ArrayCollection();
        $this->footballMatches = new ArrayCollection();
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

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getPlayersCount(): int
    {
        return $this->players->count();
    }

    public function getFootballMatchesCount(): int
    {
        return $this->footballMatches->count();
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
     * @return Collection<int, Player>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(Player $player): static
    {
        if (!$this->players->contains($player)) {
            $this->players->add($player);
            $player->setTeam($this);
        }

        return $this;
    }

    public function removePlayer(Player $player): static
    {
        if ($this->players->removeElement($player)) {
            // set the owning side to null (unless already changed)
            if ($player->getTeam() === $this) {
                $player->setTeam(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FootballMatch>
     */
    public function getFootballMatches(): Collection
    {
        return $this->footballMatches;
    }

    public function addFootballMatch(FootballMatch $footballMatch): static
    {
        if (!$this->footballMatches->contains($footballMatch)) {
            $this->footballMatches->add($footballMatch);
            $footballMatch->setTeam($this);
        }

        return $this;
    }

    public function removeFootballMatch(FootballMatch $footballMatch): static
    {
        if ($this->footballMatches->removeElement($footballMatch)) {
            // set the owning side to null (unless already changed)
            if ($footballMatch->getTeam() === $this) {
                $footballMatch->setTeam(null);
            }
        }

        return $this;
    }
}

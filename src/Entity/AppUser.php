<?php

namespace App\Entity;

use App\Repository\AppUserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: AppUserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class AppUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'adresse email n'est pas valide.")]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Assert\Count(
        min: 1,
        minMessage: 'Au moins un rôle doit être sélectionné.'
    )]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $firstname = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $lastname = null;

    #[ORM\ManyToOne(inversedBy: 'appUsers')]
    private ?Club $club = null;

    /**
     * @var Collection<int, Team>
     */
    #[ORM\ManyToMany(targetEntity: Team::class, inversedBy: 'coaches')]
    private Collection $coachedTeams;

    /**
     * @var Collection<int, Convocation>
     */
    #[ORM\OneToMany(targetEntity: Convocation::class, mappedBy: 'createdBy')]
    private Collection $createdConvocations;

    /**
     * @var Collection<int, TrainingSession>
     */
    #[ORM\OneToMany(targetEntity: TrainingSession::class, mappedBy: 'createdBy')]
    private Collection $createdTrainingSessions;

    public function __construct()
    {
        $this->coachedTeams = new ArrayCollection();
        $this->createdConvocations = new ArrayCollection();
        $this->createdTrainingSessions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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

    public function getFullName(): string
    {
        return trim(($this->firstname ?? '') . ' ' . ($this->lastname ?? ''));
    }

    public function getCoachedTeamsCount(): int
    {
        return $this->coachedTeams->count();
    }

    public function __toString(): string
    {
        return $this->getFullName() ?: $this->email ?? '';
    }

    #[Assert\Callback]
    public function validateCoachedTeamsBelongToSameClub(ExecutionContextInterface $context): void
    {
        // Si l'utilisateur est rattaché à un club,
        // les équipes qu'il encadre doivent appartenir au même club.
        if ($this->club === null) {
            return;
        }

        foreach ($this->coachedTeams as $coachedTeam) {
            if ($coachedTeam->getClub() !== $this->club) {
                $context->buildViolation("Les équipes attribuées doivent appartenir au même club que l'utilisateur.")
                    ->atPath('coachedTeams')
                    ->addViolation();

                return;
            }
        }
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
    public function getCoachedTeams(): Collection
    {
        return $this->coachedTeams;
    }

    public function addCoachedTeam(Team $coachedTeam): static
    {
        if (!$this->coachedTeams->contains($coachedTeam)) {
            $this->coachedTeams->add($coachedTeam);
        }

        return $this;
    }

    public function removeCoachedTeam(Team $coachedTeam): static
    {
        $this->coachedTeams->removeElement($coachedTeam);

        return $this;
    }

    /**
     * @return Collection<int, Convocation>
     */
    public function getCreatedConvocations(): Collection
    {
        return $this->createdConvocations;
    }

    public function addCreatedConvocation(Convocation $createdConvocation): static
    {
        if (!$this->createdConvocations->contains($createdConvocation)) {
            $this->createdConvocations->add($createdConvocation);
            $createdConvocation->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCreatedConvocation(Convocation $createdConvocation): static
    {
        if ($this->createdConvocations->removeElement($createdConvocation)) {
            // set the owning side to null (unless already changed)
            if ($createdConvocation->getCreatedBy() === $this) {
                $createdConvocation->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TrainingSession>
     */
    public function getCreatedTrainingSessions(): Collection
    {
        return $this->createdTrainingSessions;
    }

    public function addCreatedTrainingSession(TrainingSession $createdTrainingSession): static
    {
        if (!$this->createdTrainingSessions->contains($createdTrainingSession)) {
            $this->createdTrainingSessions->add($createdTrainingSession);
            $createdTrainingSession->setCreatedBy($this);
        }

        return $this;
    }

    public function removeCreatedTrainingSession(TrainingSession $createdTrainingSession): static
    {
        if ($this->createdTrainingSessions->removeElement($createdTrainingSession)) {
            // set the owning side to null (unless already changed)
            if ($createdTrainingSession->getCreatedBy() === $this) {
                $createdTrainingSession->setCreatedBy(null);
            }
        }

        return $this;
    }
}

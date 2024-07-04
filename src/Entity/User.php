<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    const ROLE_USER = 'ROLE_USER';
    const ROLE_TESTER = 'ROLE_TESTER';
    const ROLE_MODERATOR = 'ROLE_MODERATOR';
    const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'error.user.email.blank')]
    #[Assert\Email(message: 'error.user.email.invalid', mode: 'html5')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(nullable: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column]
    private ?bool $activated = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AcceptedGdpr::class, orphanRemoval: true)]
    private Collection $acceptedGdprs;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $passwordResetUntil = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $affiliation = null;

    public function __construct()
    {
        $this->acceptedGdprs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
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

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): self
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function isActivated(): ?bool
    {
        if (null === $this->activated) {
            return false;
        }
        return $this->activated;
    }

    public function setActivated(bool $activated): self
    {
        $this->activated = $activated;

        return $this;
    }

    /**
     * @return Collection<int, AcceptedGdpr>
     */
    public function getAcceptedGdprs(): Collection
    {
        return $this->acceptedGdprs;
    }

    public function addAcceptedTermsOfService(AcceptedGdpr $acceptedTermsOfService): self
    {
        if (!$this->acceptedGdprs->contains($acceptedTermsOfService)) {
            $this->acceptedGdprs->add($acceptedTermsOfService);
            $acceptedTermsOfService->setUser($this);
        }

        return $this;
    }

    public function removeAcceptedTermsOfService(AcceptedGdpr $acceptedTermsOfService): self
    {
        if ($this->acceptedGdprs->removeElement($acceptedTermsOfService)) {
            // set the owning side to null (unless already changed)
            if ($acceptedTermsOfService->getUser() === $this) {
                $acceptedTermsOfService->setUser(null);
            }
        }

        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): self
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    public function getPasswordResetUntil(): ?\DateTimeImmutable
    {
        return $this->passwordResetUntil;
    }

    public function setPasswordResetUntil(?\DateTimeImmutable $passwordResetUntil): self
    {
        $this->passwordResetUntil = $passwordResetUntil;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getAffiliation(): ?string
    {
        return $this->affiliation;
    }

    public function setAffiliation(?string $affiliation): self
    {
        $this->affiliation = $affiliation;

        return $this;
    }

    public function getNameOrEmail(): ?string
    {
        $trimmedFirstName = trim($this->firstName);
        $trimmedLastName = trim($this->lastName);
        if (!empty($trimmedFirstName) || !empty($trimmedLastName)) {
            return trim("$trimmedFirstName $trimmedLastName");
        }
        return $this->email;
    }
}

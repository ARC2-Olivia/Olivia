<?php

namespace App\Entity;

use App\Repository\AcceptedTermsOfServiceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AcceptedTermsOfServiceRepository::class)]
class AcceptedTermsOfService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TermsOfService $termsOfService = null;

    #[ORM\ManyToOne(inversedBy: 'acceptedTermsOfServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $acceptedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTermsOfService(): ?TermsOfService
    {
        return $this->termsOfService;
    }

    public function setTermsOfService(?TermsOfService $termsOfService): self
    {
        $this->termsOfService = $termsOfService;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAcceptedAt(): ?\DateTimeImmutable
    {
        return $this->acceptedAt;
    }

    public function setAcceptedAt(\DateTimeImmutable $acceptedAt): self
    {
        $this->acceptedAt = $acceptedAt;

        return $this;
    }
}

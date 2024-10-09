<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\API\Filter\CurrentUserFilter;
use App\Repository\EnrollmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ApiResource(normalizationContext: ['groups' => ['Enrollment']])]
#[GetCollection]
#[ApiFilter(CurrentUserFilter::class)]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups('Enrollment')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups('Enrollment')]
    private ?Course $course = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups('Enrollment')]
    private ?\DateTimeImmutable $enrolledAt = null;

    #[ORM\Column(nullable: true)]
    #[Groups('Enrollment')]
    private ?bool $passed = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): self
    {
        $this->course = $course;

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

    public function getEnrolledAt(): ?\DateTimeImmutable
    {
        return $this->enrolledAt;
    }

    public function setEnrolledAt(?\DateTimeImmutable $enrolledAt): self
    {
        $this->enrolledAt = $enrolledAt;

        return $this;
    }

    public function isPassed(): ?bool
    {
        if (null === $this->passed) {
            return false;
        }
        return $this->passed;
    }

    public function setPassed(?bool $passed): self
    {
        $this->passed = $passed;

        return $this;
    }
}

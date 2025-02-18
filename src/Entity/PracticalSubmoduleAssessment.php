<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleAssessmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PracticalSubmoduleAssessmentRepository::class)]
class PracticalSubmoduleAssessment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmodule $practicalSubmodule = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $takenAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSubmittedAt = null;

    #[ORM\OneToMany(mappedBy: 'practicalSubmoduleAssessment', targetEntity: PracticalSubmoduleAssessmentAnswer::class, orphanRemoval: true)]
    private Collection $practicalSubmoduleAssessmentAnswers;

    #[ORM\Column(nullable: true)]
    private ?bool $completed = null;

    public function __construct()
    {
        $this->practicalSubmoduleAssessmentAnswers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPracticalSubmodule(): ?PracticalSubmodule
    {
        return $this->practicalSubmodule;
    }

    public function setPracticalSubmodule(?PracticalSubmodule $practicalSubmodule): self
    {
        $this->practicalSubmodule = $practicalSubmodule;

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

    public function getTakenAt(): ?\DateTimeImmutable
    {
        return $this->takenAt;
    }

    public function setTakenAt(\DateTimeImmutable $takenAt): self
    {
        $this->takenAt = $takenAt;

        return $this;
    }

    public function getLastSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->lastSubmittedAt;
    }

    public function setLastSubmittedAt(?\DateTimeImmutable $lastSubmittedAt): self
    {
        $this->lastSubmittedAt = $lastSubmittedAt;

        return $this;
    }

    /**
     * @return Collection<int, PracticalSubmoduleAssessmentAnswer>
     */
    public function getPracticalSubmoduleAssessmentAnswers(): Collection
    {
        return $this->practicalSubmoduleAssessmentAnswers;
    }

    public function addPracticalSubmoduleAssessmentAnswer(PracticalSubmoduleAssessmentAnswer $practicalSubmoduleAssessmentAnswer): self
    {
        if (!$this->practicalSubmoduleAssessmentAnswers->contains($practicalSubmoduleAssessmentAnswer)) {
            $this->practicalSubmoduleAssessmentAnswers->add($practicalSubmoduleAssessmentAnswer);
            $practicalSubmoduleAssessmentAnswer->setPracticalSubmoduleAssessment($this);
        }

        return $this;
    }

    public function removePracticalSubmoduleAssessmentAnswer(PracticalSubmoduleAssessmentAnswer $practicalSubmoduleAssessmentAnswer): self
    {
        if ($this->practicalSubmoduleAssessmentAnswers->removeElement($practicalSubmoduleAssessmentAnswer)) {
            // set the owning side to null (unless already changed)
            if ($practicalSubmoduleAssessmentAnswer->getPracticalSubmoduleAssessment() === $this) {
                $practicalSubmoduleAssessmentAnswer->setPracticalSubmoduleAssessment(null);
            }
        }

        return $this;
    }

    public function isCompleted(): ?bool
    {
        if (null === $this->completed) {
            return false;
        }
        return $this->completed;
    }

    public function setCompleted(?bool $completed): self
    {
        $this->completed = $completed;

        return $this;
    }
}

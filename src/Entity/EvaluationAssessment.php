<?php

namespace App\Entity;

use App\Repository\EvaluationAssessmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationAssessmentRepository::class)]
class EvaluationAssessment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Evaluation $evaluation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $takenAt = null;

    #[ORM\OneToMany(mappedBy: 'evaluationAssessment', targetEntity: EvaluationAssessmentAnswer::class, orphanRemoval: true)]
    private Collection $evaluationAssessmentAnswers;

    public function __construct()
    {
        $this->evaluationAssessmentAnswers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluation(): ?Evaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?Evaluation $evaluation): self
    {
        $this->evaluation = $evaluation;

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

    /**
     * @return Collection<int, EvaluationAssessmentAnswer>
     */
    public function getEvaluationAssessmentAnswers(): Collection
    {
        return $this->evaluationAssessmentAnswers;
    }

    public function addEvaluationAssessmentAnswer(EvaluationAssessmentAnswer $evaluationAssessmentAnswer): self
    {
        if (!$this->evaluationAssessmentAnswers->contains($evaluationAssessmentAnswer)) {
            $this->evaluationAssessmentAnswers->add($evaluationAssessmentAnswer);
            $evaluationAssessmentAnswer->setEvaluationAssessment($this);
        }

        return $this;
    }

    public function removeEvaluationAssessmentAnswer(EvaluationAssessmentAnswer $evaluationAssessmentAnswer): self
    {
        if ($this->evaluationAssessmentAnswers->removeElement($evaluationAssessmentAnswer)) {
            // set the owning side to null (unless already changed)
            if ($evaluationAssessmentAnswer->getEvaluationAssessment() === $this) {
                $evaluationAssessmentAnswer->setEvaluationAssessment(null);
            }
        }

        return $this;
    }
}

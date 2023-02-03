<?php

namespace App\Entity;

use App\Repository\EvaluationAssessmentAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationAssessmentAnswerRepository::class)]
class EvaluationAssessmentAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'evaluationAssessmentAnswers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EvaluationAssessment $evaluationAssessment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?EvaluationQuestion $evaluationQuestion = null;

    #[ORM\Column(length: 63)]
    private ?string $givenAnswer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluationAssessment(): ?EvaluationAssessment
    {
        return $this->evaluationAssessment;
    }

    public function setEvaluationAssessment(?EvaluationAssessment $evaluationAssessment): self
    {
        $this->evaluationAssessment = $evaluationAssessment;

        return $this;
    }

    public function getEvaluationQuestion(): ?EvaluationQuestion
    {
        return $this->evaluationQuestion;
    }

    public function setEvaluationQuestion(?EvaluationQuestion $evaluationQuestion): self
    {
        $this->evaluationQuestion = $evaluationQuestion;

        return $this;
    }

    public function getGivenAnswer(): ?string
    {
        return $this->givenAnswer;
    }

    public function setGivenAnswer(string $givenAnswer): self
    {
        $this->givenAnswer = $givenAnswer;

        return $this;
    }
}

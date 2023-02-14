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

    #[ORM\ManyToOne]
    private ?EvaluationQuestionAnswer $evaluationQuestionAnswer = null;

    #[ORM\Column(length: 63)]
    private ?string $answerValue = null;

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

    public function getEvaluationQuestionAnswer(): ?EvaluationQuestionAnswer
    {
        return $this->evaluationQuestionAnswer;
    }

    public function setEvaluationQuestionAnswer(?EvaluationQuestionAnswer $evaluationQuestionAnswer): self
    {
        $this->evaluationQuestionAnswer = $evaluationQuestionAnswer;

        return $this;
    }

    public function getAnswerValue(): ?string
    {
        return $this->answerValue;
    }

    public function setAnswerValue(string $answerValue): self
    {
        $this->answerValue = $answerValue;

        return $this;
    }
}

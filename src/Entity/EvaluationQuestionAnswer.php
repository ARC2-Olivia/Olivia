<?php

namespace App\Entity;

use App\Repository\EvaluationQuestionAnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationQuestionAnswerRepository::class)]
class EvaluationQuestionAnswer extends TranslatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'evaluationQuestionAnswers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EvaluationQuestion $evaluationQuestion = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'error.evaluationQuestionAnswer.answerText')]
    #[Gedmo\Translatable]
    private ?string $answerText = null;

    #[ORM\Column(length: 63)]
    #[Assert\NotNull(message: 'error.evaluationQuestionAnswer.answerValue.weighted')]
    private ?string $answerValue = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAnswerText(): ?string
    {
        return $this->answerText;
    }

    public function setAnswerText(string $answerText): self
    {
        $this->answerText = $answerText;

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

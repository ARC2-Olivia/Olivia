<?php

namespace App\Entity;

use App\Repository\EvaluationEvaluatorSimpleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvaluationEvaluatorSimpleRepository::class)]
class EvaluationEvaluatorSimple extends TranslatableEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'evaluationEvaluatorSimple', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?EvaluationEvaluator $evaluationEvaluator = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'error.evaluationEvaluatorSimple.evaluationQuestion')]
    private ?EvaluationQuestion $evaluationQuestion = null;

    #[ORM\Column(length: 63)]
    #[Assert\NotBlank(message: 'error.evaluationEvaluatorSimple.expectedValue')]
    private ?string $expectedValue = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'error.evaluationEvaluatorSimple.resultText')]
    #[Gedmo\Translatable]
    private ?string $resultText = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluationEvaluator(): ?EvaluationEvaluator
    {
        return $this->evaluationEvaluator;
    }

    public function setEvaluationEvaluator(EvaluationEvaluator $evaluationEvaluator): self
    {
        $this->evaluationEvaluator = $evaluationEvaluator;

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

    public function getExpectedValue(): ?string
    {
        return $this->expectedValue;
    }

    public function setExpectedValue(?string $expectedValue): self
    {
        $this->expectedValue = $expectedValue;

        return $this;
    }

    public function getResultText(): ?string
    {
        return $this->resultText;
    }

    public function setResultText(?string $resultText): self
    {
        $this->resultText = $resultText;

        return $this;
    }
}

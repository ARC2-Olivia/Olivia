<?php

namespace App\Entity;

use App\Repository\EvaluationEvaluatorSimpleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $expectedValue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $resultText = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->evaluationEvaluator !== null && $this->evaluationEvaluator->isIncluded()) {
            // Expected value and result text must not be NULL if the evaluator is included in the result generation.
            if ($this->expectedValue === null) $context->buildViolation('error.evaluationEvaluatorSimple.expectedValue.blank')->atPath('expectedPath')->addViolation();
            if ($this->resultText === null) $context->buildViolation('error.evaluationEvaluatorSimple.resultText')->atPath('resultText')->addViolation();

            // Expected value has to be written in a specific way depending on the type of question. This will only be validated if the evaluator is included in the result
            // generation.
            if ($this->evaluationQuestion !== null) {
                switch ($this->evaluationQuestion->getType()) {
                    case EvaluationQuestion::TYPE_YES_NO:
                        if ($this->expectedValue !== '0' && $this->expectedValue !== '1') $context->buildViolation('error.evaluationEvaluatorSimple.expectedValue.notBool')->atPath('expectedValue')->addViolation();
                        break;
                    case EvaluationQuestion::TYPE_WEIGHTED:
                    case EvaluationQuestion::TYPE_NUMERICAL_INPUT:
                        if (!is_numeric($this->expectedValue)) $context->buildViolation('error.evaluationEvaluatorSimple.expectedValue.notNumeric')->atPath('expectedValue')->addViolation();
                        break;
                }
            }
        }
    }

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

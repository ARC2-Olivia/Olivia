<?php

namespace App\Entity;

use App\Repository\EvaluationEvaluatorProductAggregateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: EvaluationEvaluatorProductAggregateRepository::class)]
class EvaluationEvaluatorProductAggregate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'evaluationEvaluatorProductAggregate', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?EvaluationEvaluator $evaluationEvaluator = null;

    #[ORM\ManyToMany(targetEntity: EvaluationQuestion::class)]
    private Collection $evaluationQuestions;

    #[ORM\Column(nullable: true)]
    private ?int $expectedValueRangeStart = null;

    #[ORM\Column(nullable: true)]
    private ?int $expectedValueRangeEnd = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resultText = null;

    public function __construct()
    {
        $this->evaluationQuestions = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->evaluationEvaluator !== null && $this->evaluationEvaluator->isIncluded()) {
            $this->validateEvaluationQuestions($context);
            $this->validateExpectedValueRanges($context);
            $this->validateResultText($context);
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

    /**
     * @return Collection<int, EvaluationQuestion>
     */
    public function getEvaluationQuestions(): Collection
    {
        return $this->evaluationQuestions;
    }

    public function addEvaluationQuestion(EvaluationQuestion $evaluationQuestion): self
    {
        if (!$this->evaluationQuestions->contains($evaluationQuestion)) {
            $this->evaluationQuestions->add($evaluationQuestion);
        }

        return $this;
    }

    public function removeEvaluationQuestion(EvaluationQuestion $evaluationQuestion): self
    {
        $this->evaluationQuestions->removeElement($evaluationQuestion);

        return $this;
    }

    public function getExpectedValueRangeStart(): ?int
    {
        return $this->expectedValueRangeStart;
    }

    public function setExpectedValueRangeStart(?int $expectedValueRangeStart): self
    {
        $this->expectedValueRangeStart = $expectedValueRangeStart;

        return $this;
    }

    public function getExpectedValueRangeEnd(): ?int
    {
        return $this->expectedValueRangeEnd;
    }

    public function setExpectedValueRangeEnd(?int $expectedValueRangeEnd): self
    {
        $this->expectedValueRangeEnd = $expectedValueRangeEnd;

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

    private function validateEvaluationQuestions(ExecutionContextInterface $context)
    {
        if ($this->evaluationQuestions->isEmpty()) {
            $context->buildViolation('error.evaluationEvaluatorProductAggregate.evaluationQuestions')->atPath('evaluationQuestions')->addViolation();
        }
    }

    private function validateExpectedValueRanges(ExecutionContextInterface $context): void
    {
        $startIsNumeric = is_numeric($this->getExpectedValueRangeStart());
        $endIsNumeric = is_numeric($this->getExpectedValueRangeEnd());

        if (!$startIsNumeric) $context->buildViolation('error.evaluationEvaluatorProductAggregate.expectedValueRange.start')->atPath('expectedValueRangeStart')->addViolation();
        if (!$endIsNumeric) $context->buildViolation('error.evaluationEvaluatorProductAggregate.expectedValueRange.end')->atPath('expectedValueRangeEnd')->addViolation();
        if ($startIsNumeric && $endIsNumeric && $this->getExpectedValueRangeStart() > $this->getExpectedValueRangeEnd()) {
            $context->buildViolation('error.evaluationEvaluatorSumAggregate.expectedValueRange.invalid')->addViolation();
        }
    }

    private function validateResultText(ExecutionContextInterface $context): void
    {
        if ($this->resultText === null && trim($this->resultText) === '' ) {
            $context->buildViolation('error.evaluationEvaluatorProductAggregate.resultText')->atPath('resultText')->addViolation();
        }
    }
}

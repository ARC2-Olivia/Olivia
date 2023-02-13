<?php

namespace App\Entity;

use App\Repository\EvaluationEvaluatorSumAggregateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[ORM\Entity(repositoryClass: EvaluationEvaluatorSumAggregateRepository::class)]
class EvaluationEvaluatorSumAggregate extends TranslatableEntity implements EvaluationEvaluatorImplementationInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'evaluationEvaluatorSumAggregate', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?EvaluationEvaluator $evaluationEvaluator = null;

    #[ORM\ManyToMany(targetEntity: EvaluationQuestion::class)]
    private Collection $evaluationQuestions;

    #[ORM\ManyToMany(targetEntity: EvaluationEvaluator::class)]
    private Collection $evaluationEvaluators;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $expectedValueRangeStart = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $expectedValueRangeEnd = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $resultText = null;

    public function __construct()
    {
        $this->evaluationQuestions = new ArrayCollection();
        $this->evaluationEvaluators = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->evaluationEvaluator !== null && $this->evaluationEvaluator->isIncluded()) {
            $this->validateEvaluationQuestionsAndEvaluators($context);
            $this->validateExpectedValueRanges($context);
            $this->validateResultText($context);
        }
    }

    public function calculateResult(EvaluationAssessment $evaluationAssessment, ValidatorInterface $validator = null): int
    {
        $sum = 0;
        foreach ($this->getEvaluationQuestions() as $evaluationQuestion) {
            foreach ($evaluationAssessment->getEvaluationAssessmentAnswers() as $assessmentAnswer) {
                if ($assessmentAnswer->getEvaluationQuestion()->getId() === $evaluationQuestion->getId()) {
                    $sum += $assessmentAnswer->getGivenAnswer();
                    break;
                }
            }
        }
        foreach ($this->getEvaluationEvaluators() as $evaluationEvaluator) {
            $evaluationEvaluatorImplementation = $evaluationEvaluator->getEvaluationEvaluatorImplementation();
            if ($validator !== null && $validator->validate($evaluationEvaluatorImplementation)->count() === 0) {
                $sum += $evaluationEvaluatorImplementation->calculateResult($evaluationAssessment, $validator);
            }
        }
        return $sum;
    }

    public function checkConformity(EvaluationAssessment $evaluationAssessment, ValidatorInterface $validator = null): bool
    {
        $result = $this->calculateResult($evaluationAssessment, $validator);
        return $result >= $this->expectedValueRangeStart && $result <= $this->expectedValueRangeEnd;
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

    /**
     * @return Collection<int, EvaluationEvaluator>
     */
    public function getEvaluationEvaluators(): Collection
    {
        return $this->evaluationEvaluators;
    }

    public function addEvaluationEvaluator(EvaluationEvaluator $evaluationEvaluator): self
    {
        if (!$this->evaluationEvaluators->contains($evaluationEvaluator)) {
            $this->evaluationEvaluators->add($evaluationEvaluator);
        }

        return $this;
    }

    public function removeEvaluationEvaluator(EvaluationEvaluator $evaluationEvaluator): self
    {
        $this->evaluationEvaluators->removeElement($evaluationEvaluator);

        return $this;
    }

    public function getExpectedValueRangeStart(): ?string
    {
        return $this->expectedValueRangeStart;
    }

    public function setExpectedValueRangeStart(?string $expectedValueRangeStart): self
    {
        $this->expectedValueRangeStart = $expectedValueRangeStart;

        return $this;
    }

    public function getExpectedValueRangeEnd(): ?string
    {
        return $this->expectedValueRangeEnd;
    }

    public function setExpectedValueRangeEnd(?string $expectedValueRangeEnd): self
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

    private function validateEvaluationQuestionsAndEvaluators(ExecutionContextInterface $context)
    {
        if ($this->evaluationQuestions->isEmpty() && $this->evaluationEvaluators->isEmpty()) {
            $context->buildViolation('error.evaluationEvaluatorSumAggregate.evaluationQuestionsAndEvaluators')->addViolation();
        }
    }

    private function validateExpectedValueRanges(ExecutionContextInterface $context): void
    {
        $startIsNumeric = is_numeric($this->getExpectedValueRangeStart());
        $endIsNumeric = is_numeric($this->getExpectedValueRangeEnd());

        if (!$startIsNumeric) $context->buildViolation('error.evaluationEvaluatorSumAggregate.expectedValueRange.start')->atPath('expectedValueRangeStart')->addViolation();
        if (!$endIsNumeric) $context->buildViolation('error.evaluationEvaluatorSumAggregate.expectedValueRange.end')->atPath('expectedValueRangeEnd')->addViolation();
        if ($startIsNumeric && $endIsNumeric && $this->getExpectedValueRangeStart() > $this->getExpectedValueRangeEnd()) {
            $context->buildViolation('error.evaluationEvaluatorSumAggregate.expectedValueRange.invalid')->addViolation();
        }
    }

    private function validateResultText(ExecutionContextInterface $context): void
    {
        if ($this->resultText === null && trim($this->resultText) === '' ) {
            $context->buildViolation('error.evaluationEvaluatorSumAggregate.resultText')->atPath('resultText')->addViolation();
        }
    }
}

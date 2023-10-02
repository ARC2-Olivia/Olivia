<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorMaxValueRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorMaxValueRepository::class)]
class PracticalSubmoduleProcessorMaxValue implements PracticalSubmoduleProcessorImplementationInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'practicalSubmoduleProcessorMaxValue', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmoduleProcessor $practicalSubmoduleProcessor = null;

    #[ORM\ManyToMany(targetEntity: PracticalSubmoduleQuestion::class)]
    #[ORM\JoinTable(name: 'practical_submodule_processor_max_value_question')]
    private Collection $practicalSubmoduleQuestions;

    #[ORM\ManyToMany(targetEntity: PracticalSubmoduleProcessor::class)]
    #[ORM\JoinTable(name: 'practical_submodule_processor_max_value_processor')]
    private Collection $practicalSubmoduleProcessors;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $expectedValueRangeStart = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $expectedValueRangeEnd = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resultText = null;

    #[Assert\Callback] public function validate(ExecutionContextInterface $context, $payload): void
    {
        if (null !== $this->practicalSubmoduleProcessor && $this->practicalSubmoduleProcessor->isIncluded()) {
            $this->validateProcessors($context);
            $this->validateExpectedValueRange($context);
            $this->validateResultText($context);
        }
    }

    public function calculateResult(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null): int
    {
        $maxValue = PHP_INT_MIN;

        foreach ($this->getPracticalSubmoduleQuestions() as $question) {
            foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $assessmentAnswer) {
                if ($question->getId() === $assessmentAnswer->getPracticalSubmoduleQuestion()->getId() && $maxValue < $assessmentAnswer->getAnswerValue()) {
                    $maxValue = $assessmentAnswer->getAnswerValue();
                }
            }
        }

        foreach ($this->getPracticalSubmoduleProcessors() as $processor) {
            $value = match ($processor->getType()) {
                PracticalSubmoduleProcessor::TYPE_MAX_VALUE => $processor->getPracticalSubmoduleProcessorMaxValue()->calculateResult($practicalSubmoduleAssessment, $validator),
                PracticalSubmoduleProcessor::TYPE_SUM_AGGREGATE => $processor->getPracticalSubmoduleProcessorSumAggregate()->calculateResult($practicalSubmoduleAssessment, $validator),
                PracticalSubmoduleProcessor::TYPE_PRODUCT_AGGREGATE => $processor->getPracticalSubmoduleProcessorProductAggregate()->calculateResult($practicalSubmoduleAssessment, $validator),
                default => PHP_INT_MIN
            };
            if ($maxValue < $value) {
                $maxValue = $value;
            }
        }

        return $maxValue;
    }

    public function checkConformity(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null): bool
    {
        if (null === $this->practicalSubmoduleProcessor) return false;
        $result = $this->calculateResult($practicalSubmoduleAssessment, $validator);
        return $this->practicalSubmoduleProcessor->isDependencyConditionPassing($practicalSubmoduleAssessment)
            && $result >= $this->expectedValueRangeStart
            && $result < $this->expectedValueRangeEnd
        ;
    }

    public function __construct()
    {
        $this->practicalSubmoduleProcessors = new ArrayCollection();
        $this->practicalSubmoduleQuestions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPracticalSubmoduleProcessor(): ?PracticalSubmoduleProcessor
    {
        return $this->practicalSubmoduleProcessor;
    }

    public function setPracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        $this->practicalSubmoduleProcessor = $practicalSubmoduleProcessor;

        return $this;
    }

    /**
     * @return Collection<int, PracticalSubmoduleQuestion>
     */
    public function getPracticalSubmoduleQuestions(): Collection
    {
        return $this->practicalSubmoduleQuestions;
    }

    public function addPracticalSubmoduleQuestion(PracticalSubmoduleQuestion $practicalSubmoduleQuestion): self
    {
        if (!$this->practicalSubmoduleQuestions->contains($practicalSubmoduleQuestion)) {
            $this->practicalSubmoduleQuestions->add($practicalSubmoduleQuestion);
        }

        return $this;
    }

    public function removePracticalSubmoduleQuestion(PracticalSubmoduleQuestion $practicalSubmoduleQuestion): self
    {
        $this->practicalSubmoduleQuestions->removeElement($practicalSubmoduleQuestion);

        return $this;
    }

    /**
     * @return Collection<int, PracticalSubmoduleProcessor>
     */
    public function getPracticalSubmoduleProcessors(): Collection
    {
        return $this->practicalSubmoduleProcessors;
    }

    public function addPracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        if (!$this->practicalSubmoduleProcessors->contains($practicalSubmoduleProcessor)) {
            $this->practicalSubmoduleProcessors->add($practicalSubmoduleProcessor);
        }

        return $this;
    }

    public function removePracticalSubmoduleProcessor(PracticalSubmoduleProcessor $practicalSubmoduleProcessor): self
    {
        $this->practicalSubmoduleProcessors->removeElement($practicalSubmoduleProcessor);

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

    private function validateProcessors(ExecutionContextInterface $context): void
    {
        if ($this->practicalSubmoduleProcessors->isEmpty() && $this->practicalSubmoduleQuestions->isEmpty()) {
            $context->buildViolation('error.practicalSubmoduleProcessorMaxValue.questionsAndProcessors')->addViolation();
        }
    }

    private function validateExpectedValueRange(ExecutionContextInterface $context): void
    {
        $startIsNumeric = is_numeric($this->getExpectedValueRangeStart());
        $endIsNumeric = is_numeric($this->getExpectedValueRangeEnd());

        if (!$startIsNumeric) $context->buildViolation('error.practicalSubmoduleMaxValue.expectedValueRange.start')->atPath('expectedValueRangeStart')->addViolation();
        if (!$endIsNumeric) $context->buildViolation('error.practicalSubmoduleProcessorMaxValue.expectedValueRange.end')->atPath('expectedValueRangeEnd')->addViolation();
        if ($startIsNumeric && $endIsNumeric && $this->getExpectedValueRangeStart() > $this->getExpectedValueRangeEnd()) {
            $context->buildViolation('error.practicalSubmoduleProcessorMaxValue.expectedValueRange.invalid')->addViolation();
        }
    }

    private function validateResultText(ExecutionContextInterface $context): void
    {
        if ($this->resultText === null && trim($this->resultText) === '') {
            $context->buildViolation('error.practicalSubmoduleProcessorMaxValue.resultText')->atPath('resultText')->addViolation();
        }
    }
}

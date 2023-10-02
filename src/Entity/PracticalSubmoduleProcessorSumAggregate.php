<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorSumAggregateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorSumAggregateRepository::class)]
class PracticalSubmoduleProcessorSumAggregate extends TranslatableEntity implements PracticalSubmoduleProcessorImplementationInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'practicalSubmoduleProcessorSumAggregate', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmoduleProcessor $practicalSubmoduleProcessor = null;

    #[ORM\ManyToMany(targetEntity: PracticalSubmoduleQuestion::class)]
    #[ORM\JoinTable(name: 'practical_submodule_processor_sum_aggregate_question')]
    private Collection $practicalSubmoduleQuestions;

    #[ORM\ManyToMany(targetEntity: PracticalSubmoduleProcessor::class)]
    #[ORM\JoinTable(name: 'practical_submodule_processor_sum_aggregate_processor')]
    private Collection $practicalSubmoduleProcessors;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $expectedValueRangeStart = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $expectedValueRangeEnd = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $resultText = null;

    private ?string $processedText = null;

    public function __construct()
    {
        $this->practicalSubmoduleQuestions = new ArrayCollection();
        $this->practicalSubmoduleProcessors = new ArrayCollection();
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->practicalSubmoduleProcessor !== null && $this->practicalSubmoduleProcessor->isIncluded()) {
            $this->validatePracticalSubmoduleQuestionsAndProcessors($context);
            $this->validateExpectedValueRanges($context);
            $this->validateResultText($context);
        }
    }

    public function calculateResult(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null): int
    {
        $sum = 0.0;
        foreach ($this->getPracticalSubmoduleQuestions() as $question) {
            foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $assessmentAnswer) {
                if ($assessmentAnswer->getPracticalSubmoduleQuestion()->getId() === $question->getId()) {
                    $sum += $assessmentAnswer->getAnswerValue();
                }
            }
        }
        foreach ($this->getPracticalSubmoduleProcessors() as $processor) {
            $processorImpl = $processor->getImplementation();
            if ($validator !== null && $validator->validate($processorImpl)->count() === 0) {
                $sum += $processorImpl->calculateResult($practicalSubmoduleAssessment, $validator);
            }
        }

        $pattern = '/\{\{\s*value\s*\}\}/i';
        if (1 === preg_match($pattern, $this->resultText)) {
            $this->processedText = $this->resultText;
            $this->processedText = preg_replace($pattern, number_format($sum, 2, ',', '.'), $this->processedText);
        }

        return $sum;
    }

    public function checkConformity(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null): bool
    {
        $result = $this->calculateResult($practicalSubmoduleAssessment, $validator);
        return $this->practicalSubmoduleProcessor->isDependencyConditionPassing($practicalSubmoduleAssessment)
            && $result >= $this->expectedValueRangeStart
            && $result < $this->expectedValueRangeEnd;
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
        if (null !== $this->processedText) {
            return $this->processedText;
        }
        return $this->resultText;
    }

    public function setResultText(?string $resultText): self
    {
        $this->resultText = $resultText;

        return $this;
    }

    private function validatePracticalSubmoduleQuestionsAndProcessors(ExecutionContextInterface $context)
    {
        if ($this->practicalSubmoduleQuestions->isEmpty() && $this->practicalSubmoduleProcessors->isEmpty()) {
            $context->buildViolation('error.practicalSubmoduleProcessorSumAggregate.questionsAndProcessors')->addViolation();
        }
    }

    private function validateExpectedValueRanges(ExecutionContextInterface $context): void
    {
        $startIsNumeric = is_numeric($this->getExpectedValueRangeStart());
        $endIsNumeric = is_numeric($this->getExpectedValueRangeEnd());

        if (!$startIsNumeric) $context->buildViolation('error.practicalSubmoduleProcessorSumAggregate.expectedValueRange.start')->atPath('expectedValueRangeStart')->addViolation();
        if (!$endIsNumeric) $context->buildViolation('error.practicalSubmoduleProcessorSumAggregate.expectedValueRange.end')->atPath('expectedValueRangeEnd')->addViolation();
        if ($startIsNumeric && $endIsNumeric && $this->getExpectedValueRangeStart() > $this->getExpectedValueRangeEnd()) {
            $context->buildViolation('error.practicalSubmoduleProcessorSumAggregate.expectedValueRange.invalid')->addViolation();
        }
    }

    private function validateResultText(ExecutionContextInterface $context): void
    {
        if ($this->resultText === null && trim($this->resultText) === '' ) {
            $context->buildViolation('error.practicalSubmoduleProcessorSumAggregate.resultText')->atPath('resultText')->addViolation();
        }
    }
}

<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorSimpleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorSimpleRepository::class)]
class PracticalSubmoduleProcessorSimple extends TranslatableEntity implements PracticalSubmoduleProcessorImplementationInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'practicalSubmoduleProcessorSimple', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmoduleProcessor $practicalSubmoduleProcessor = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'error.practicalSubmoduleProcessorSimple.evaluationQuestion')]
    private ?PracticalSubmoduleQuestion $practicalSubmoduleQuestion = null;

    #[ORM\Column(length: 63, nullable: true)]
    private ?string $expectedValue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $resultText = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if ($this->practicalSubmoduleProcessor !== null && $this->practicalSubmoduleProcessor->isIncluded()) {
            $this->validateExpectedValue($context);
            $this->validateResultText($context);
        }
    }

    public function calculateResult(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null): bool
    {
        foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $assessmentAnswer) {
            if ($assessmentAnswer->getPracticalSubmoduleQuestion()->getId() === $this->practicalSubmoduleQuestion->getId()) {
                $givenAnswer = $assessmentAnswer->getAnswerValue();
                $expectedAnswer = $this->getExpectedValue();

                if ($this->getPracticalSubmoduleQuestion()->getType() === PracticalSubmoduleQuestion::TYPE_YES_NO) {
                    $givenAnswer = (bool)$givenAnswer;
                    $expectedAnswer = (bool)$expectedAnswer;
                }

                return $givenAnswer === $expectedAnswer;
            }
        }

        return false;
    }

    public function checkConformity(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null): bool
    {
        return $this->practicalSubmoduleProcessor->isDependencyConditionPassing($practicalSubmoduleAssessment) && $this->calculateResult($practicalSubmoduleAssessment, $validator);
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

    public function getPracticalSubmoduleQuestion(): ?PracticalSubmoduleQuestion
    {
        return $this->practicalSubmoduleQuestion;
    }

    public function setPracticalSubmoduleQuestion(?PracticalSubmoduleQuestion $practicalSubmoduleQuestion): self
    {
        $this->practicalSubmoduleQuestion = $practicalSubmoduleQuestion;

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

    private function validateExpectedValue(ExecutionContextInterface $context): void
    {
        if ($this->expectedValue === null) {
            $context->buildViolation('error.practicalSubmoduleProcessorSimple.expectedValue.blank')->atPath('expectedPath')->addViolation();
        }

        if ($this->practicalSubmoduleQuestion !== null) {
            switch ($this->practicalSubmoduleQuestion->getType()) {
                case PracticalSubmoduleQuestion::TYPE_YES_NO:
                    if ($this->expectedValue !== '0' && $this->expectedValue !== '1') $context->buildViolation('error.practicalSubmoduleProcessorSimple.expectedValue.notBool')->atPath('expectedValue')->addViolation();
                    break;
                case PracticalSubmoduleQuestion::TYPE_WEIGHTED:
                case PracticalSubmoduleQuestion::TYPE_NUMERICAL_INPUT:
                    if (!is_numeric($this->expectedValue)) $context->buildViolation('error.practicalSubmoduleProcessorSimple.expectedValue.notNumeric')->atPath('expectedValue')->addViolation();
                    break;
            }
        }
    }

    private function validateResultText(ExecutionContextInterface $context): void
    {
        if ($this->resultText === null) {
            $context->buildViolation('error.practicalSubmoduleProcessorSimple.resultText')->atPath('resultText')->addViolation();
        }
    }
}

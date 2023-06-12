<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleProcessorTemplatedTextRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[ORM\Entity(repositoryClass: PracticalSubmoduleProcessorTemplatedTextRepository::class)]
class PracticalSubmoduleProcessorTemplatedText extends TranslatableEntity implements PracticalSubmoduleProcessorImplementationInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmoduleProcessor $practicalSubmoduleProcessor = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?PracticalSubmoduleQuestion $practicalSubmoduleQuestion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable]
    private ?string $resultText = null;

    private ?string $processedText = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
    }

    public function calculateResult(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null)
    {
        $questionType = $this->practicalSubmoduleQuestion?->getType();

        if ($questionType === PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT && !$practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers()->isEmpty()) {
            $this->handleTemplatingForTemplatedTextQuestion($practicalSubmoduleAssessment);
        } else if (in_array($questionType, PracticalSubmoduleQuestion::getSingleChoiceTypes())) {
            $this->handleTemplatingForSingleChoiceQuestion($practicalSubmoduleAssessment);
        } else if ($questionType === PracticalSubmoduleQuestion::TYPE_MULTI_CHOICE) {
            $this->handleTemplatingForMultiChoiceQuestion($practicalSubmoduleAssessment);
        } else if ($questionType !== null) {
            $this->handleDefaultTemplating($practicalSubmoduleAssessment);
        }

        $this->handleDateTemplating();
    }

    public function checkConformity(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null): bool
    {
        if ($this->practicalSubmoduleQuestion === null) {
            return true;
        }

        foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $assessmentAnswer) {
            if ($this->practicalSubmoduleQuestion->getId() === $assessmentAnswer->getPracticalSubmoduleQuestion()->getId()) {
                return true;
            }
        }

        return false;
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

    public function getResultText(): ?string
    {
        if ($this->processedText !== null) {
            return $this->processedText;
        }
        return $this->resultText;
    }

    public function setResultText(?string $resultText): self
    {
        $this->resultText = $resultText;

        return $this;
    }

    private function handleTemplatingForTemplatedTextQuestion(PracticalSubmoduleAssessment $practicalSubmoduleAssessment): void
    {
        foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $assessmentAnswer) {
            if ($assessmentAnswer->getPracticalSubmoduleQuestion()->getId() === $this->practicalSubmoduleQuestion->getId()) {
                $this->processedText = $this->resultText;
                $givenAnswer = json_decode($assessmentAnswer->getAnswerValue(), true);
                foreach ($givenAnswer as $field => $value) {
                    $pattern = '/\{\{\s*' . $field . '\s*\}\}/';
                    $this->processedText = preg_replace($pattern, $value, $this->processedText);
                }
                break;
            }
        }
    }

    private function handleTemplatingForSingleChoiceQuestion(PracticalSubmoduleAssessment $practicalSubmoduleAssessment): void
    {
        foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $assessmentAnswer) {
            if ($assessmentAnswer->getPracticalSubmoduleQuestion()->getId() === $this->practicalSubmoduleQuestion->getId() && $assessmentAnswer->getPracticalSubmoduleQuestionAnswer() !== null) {
                $this->processedText = $this->resultText;
                $questionAnswer = $assessmentAnswer->getPracticalSubmoduleQuestionAnswer();
                $givenAnswer = $questionAnswer->getAnswerText();
                $pattern = '/\{\{\s*value\s*\}\}/i';
                $this->processedText = preg_replace($pattern, $givenAnswer, $this->processedText);
                break;
            }
        }
    }

    private function handleTemplatingForMultiChoiceQuestion(PracticalSubmoduleAssessment $practicalSubmoduleAssessment): void
    {
        $gatheredAnswers = [];
        foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $assessmentAnswer) {
            if ($assessmentAnswer->getPracticalSubmoduleQuestion()->getId() === $this->practicalSubmoduleQuestion->getId()) {
                $gatheredAnswers[] = $assessmentAnswer->getPracticalSubmoduleQuestionAnswer()->getAnswerText();
            }
        }
        if (count($gatheredAnswers) > 0) {
            $this->processedText = $this->resultText;
            $pattern = '/\{\{\s*values_as_list\s*\}\}/i';
            if (preg_match($pattern, $this->processedText)) {
                $gatheredAnswersAsList = $gatheredAnswers;
                for ($i = 0; $i < count($gatheredAnswersAsList); $i++) {
                    $gatheredAnswersAsList[$i] = '- ' . $gatheredAnswersAsList[$i];
                }
                $gatheredAnswersAsList = implode("\n", $gatheredAnswersAsList);
                $this->processedText = preg_replace($pattern, $gatheredAnswersAsList, $this->processedText);
            }
            $pattern = '/\{\{\s*values_one_line\s*\}\}/i';
            if (preg_match($pattern, $this->processedText)) {
                $gatheredAnswersOneLine = implode(', ', $gatheredAnswers);
                $this->processedText = preg_replace($pattern, $gatheredAnswersOneLine, $this->processedText);
            }
        }
    }

    private function handleDefaultTemplating(PracticalSubmoduleAssessment $practicalSubmoduleAssessment): void
    {
        foreach ($practicalSubmoduleAssessment->getPracticalSubmoduleAssessmentAnswers() as $assessmentAnswer) {
            if ($assessmentAnswer->getPracticalSubmoduleQuestion()->getId() === $this->practicalSubmoduleQuestion->getId()) {
                $this->processedText = $this->resultText;
                $givenAnswer = $assessmentAnswer->getAnswerValue();
                $pattern = '/\{\{\s*value\s*\}\}/i';
                $this->processedText = preg_replace($pattern, $givenAnswer, $this->processedText);
                break;
            }
        }
    }

    private function handleDateTemplating(): void
    {
        if ($this->processedText === null) {
            $this->processedText = $this->resultText;
        }

        $pattern = '/\{\{\s*DATE_NOW\s*\}\}/i';
        $date = (new \DateTime())->format('d.m.Y.');
        if (preg_match($pattern, $this->processedText)) {
            $this->processedText = preg_replace($pattern, $date, $this->processedText);
        }
    }
}

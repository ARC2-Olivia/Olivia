<?php

namespace App\Entity;

use App\Repository\PracticalSubmoduleAssessmentAnswerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PracticalSubmoduleAssessmentAnswerRepository::class)]
class PracticalSubmoduleAssessmentAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'practicalSubmoduleAssessmentAnswers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmoduleAssessment $practicalSubmoduleAssessment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?PracticalSubmoduleQuestion $practicalSubmoduleQuestion = null;

    #[ORM\ManyToOne]
    private ?PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $answerValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPracticalSubmoduleAssessment(): ?PracticalSubmoduleAssessment
    {
        return $this->practicalSubmoduleAssessment;
    }

    public function setPracticalSubmoduleAssessment(?PracticalSubmoduleAssessment $practicalSubmoduleAssessment): self
    {
        $this->practicalSubmoduleAssessment = $practicalSubmoduleAssessment;

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

    public function getPracticalSubmoduleQuestionAnswer(): ?PracticalSubmoduleQuestionAnswer
    {
        return $this->practicalSubmoduleQuestionAnswer;
    }

    public function setPracticalSubmoduleQuestionAnswer(?PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer): self
    {
        $this->practicalSubmoduleQuestionAnswer = $practicalSubmoduleQuestionAnswer;

        return $this;
    }

    public function getAnswerValue(): ?string
    {
        return $this->answerValue;
    }

    public function setAnswerValue(?string $answerValue): self
    {
        $this->answerValue = $answerValue;

        return $this;
    }

    public function getDisplayableAnswer(): ?string
    {
        if ($this->practicalSubmoduleQuestionAnswer !== null) {
            return $this->practicalSubmoduleQuestionAnswer->getAnswerText();
        }

        if (PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT === $this->practicalSubmoduleQuestion->getType()) {
            $answeredFields = json_decode($this->getAnswerValue(), true);
            $displayableAnswer = $this->practicalSubmoduleQuestion->getPracticalSubmoduleQuestionAnswers()->get(0)->getAnswerText();
            foreach ($answeredFields as $field => $answer) {
                $pattern = '/\{\{\s*'.$field.'[\|\s\w]*\s*\}\}/';
                $replacement = '<b>'.$answer.'</b>';
                $displayableAnswer = preg_replace($pattern, $replacement, $displayableAnswer);
            }
            return $displayableAnswer;
        }

        if (PracticalSubmoduleQuestion::TYPE_TEMPLATED_LIST_INPUT === $this->practicalSubmoduleQuestion->getType()) {
            $answers = json_decode($this->getAnswerValue(), true);
            $displayableAnswers = [];
            foreach ($answers as $answer) {
                $displayableAnswer = $this->practicalSubmoduleQuestion->getTemplate();
                foreach ($answer as $variable => $value) {
                    $pattern = '/\{\{\s*'.$variable.'[\|\s\w]*\s*\}\}/';
                    $replacement = '<b>'.htmlspecialchars($value).'</b>';
                    $displayableAnswer = preg_replace($pattern, $replacement, $displayableAnswer);
                }
                $displayableAnswers[] = $displayableAnswer;
            }
            return implode('<br/><br/>', $displayableAnswers);
        }

        return $this->answerValue;
    }
}

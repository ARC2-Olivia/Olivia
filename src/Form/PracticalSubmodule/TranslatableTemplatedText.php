<?php

namespace App\Form\PracticalSubmodule;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TranslatableTemplatedText
{
    public const PATTERN_TEXT_FIELD = '/\{\{\s*(\w+)\s*\}\}/';

    private ?string $text = null;
    private ?string $translatedText = null;

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void
    {
        if (!$this->hasEqualTextFields($this->getTextFields(), $this->getTranslatedTextFields())) {
            $context->buildViolation('error.practicalSubmoduleQuestionAnswer.templatedText.textFields')->addViolation();
        }
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getTranslatedText(): ?string
    {
        return $this->translatedText;
    }

    public function setTranslatedText(?string $translatedText): self
    {
        $this->translatedText = $translatedText;

        return $this;
    }

    public function getTextFields(): array
    {
        $matches = null;
        preg_match_all(self::PATTERN_TEXT_FIELD, $this->text, $matches);
        return empty($matches) ? [] : $matches[1];
    }

    public function getTranslatedTextFields(): array
    {
        $matches = null;
        preg_match_all(self::PATTERN_TEXT_FIELD, $this->translatedText, $matches);
        return empty($matches) ? [] : $matches[1];
    }

    private function hasEqualTextFields(array $fieldsA, array $fieldsB): bool
    {
        $diffA = array_diff($fieldsA, $fieldsB);
        $diffB = array_diff($fieldsB, $fieldsA);
        return empty($diffA) && empty($diffB);
    }

}
<?php

namespace App\Form\PracticalSubmodule;

use App\Misc\TemplatedTextField;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TranslatableTemplatedText
{
    public const PATTERN_TEXT_FIELD = '/\{\{\s*(\w+)\s*(?:\|\s*([\w\s\|]*)\s*)?\}\}/';

    private ?string $text = null;
    private ?string $translatedText = null;

    /** @var TemplatedTextField[]  */
    private ?array $textFields = null;

    /** @var TemplatedTextField[]  */
    private ?array $translatedTextFields = null;

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

    /** @return TemplatedTextField[] */
    public function getTextFields(): array
    {
        if (null === $this->textFields) {
            $this->textFields = $this->getFields($this->text);
        }
        return $this->textFields;
    }

    /** @return TemplatedTextField[] */
    public function getTranslatedTextFields(): array
    {
        if (null === $this->translatedTextFields) {
            $this->translatedTextFields = $this->getFields($this->translatedText);
        }
        return $this->translatedTextFields;
    }

    /**
     * @param TemplatedTextField[] $fieldsA
     * @param TemplatedTextField[] $fieldsB
     * @return bool
     */
    private function hasEqualTextFields(array $fieldsA, array $fieldsB): bool
    {
        $mapper = function (TemplatedTextField $field) { return $field->getName(); };
        $fieldNamesA = array_map($mapper, $fieldsA);
        $fieldNamesB = array_map($mapper, $fieldsB);
        $diffA = array_diff($fieldNamesA, $fieldNamesB);
        $diffB = array_diff($fieldNamesB, $fieldNamesA);
        return empty($diffA) && empty($diffB);
    }

    /** @return TemplatedTextField[] */
    private function getFields(?string $string): array
    {
        $fields = [];
        $matches = null;
        $count = preg_match_all(self::PATTERN_TEXT_FIELD, $string, $matches);

        if (is_integer($count)) {
            for ($i = 0; $i < $count; $i++) {
                $fieldName = $matches[1][$i];
                $fieldProps = [];
                foreach (explode('|', $matches[2][$i]) as $prop) {
                    $prop = trim($prop);
                    if ($prop !== "") {
                        $fieldProps[] = trim($prop);
                    }
                }
                $fields[] = (new TemplatedTextField())->setName($fieldName)->setProperties($fieldProps);
            }
        }

        return $fields;
    }

}
<?php

namespace App\Misc;

use Doctrine\Common\Collections\ArrayCollection;

class TemplatedTextField
{
    private ?string $name = null;
    private array $properties = [];

    public static function simplifyField(TemplatedTextField $field): string
    {
        if (true === empty($field->properties)) {
            return $field->name;
        }
        return $field->name . '|' . implode('|', $field->properties);
    }

    public static function desimplifyField(string $simplifiedField): ?TemplatedTextField
    {
        $fieldParts = explode('|', $simplifiedField);
        if (null === $fieldParts) return null;

        $fieldPartsCount = count($fieldParts);
        if ($fieldPartsCount === 0) return null;

        $templatedTextField = (new TemplatedTextField())->setName($fieldParts[0]);
        if ($fieldPartsCount > 1) {
            $templatedTextField->setProperties(array_splice($fieldParts, 1));
        }

        return $templatedTextField;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): self
    {
        $this->properties = $properties;
        return $this;
    }

    public function simplify(): string
    {
        return self::simplifyField($this);
    }

    public function asArray()
    {
        return ['name' => $this->name, 'properties' => $this->properties];
    }
}
<?php

namespace App\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class SimpleArrayToStringTransformer implements DataTransformerInterface
{

    public function transform(mixed $valueAsArray): ?string
    {
        if ($valueAsArray === null) return null;
        if (!\is_array($valueAsArray)) throw new TransformationFailedException('Expected an array.');
        return implode(',', $valueAsArray);
    }

    public function reverseTransform(mixed $valueAsString): array
    {
        if (is_string($valueAsString)) return explode(',', $valueAsString);
        return [];
    }
}
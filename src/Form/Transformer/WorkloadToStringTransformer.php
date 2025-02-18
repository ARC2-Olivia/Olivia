<?php

namespace App\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class WorkloadToStringTransformer implements DataTransformerInterface
{
    const FIELD_WORKLOAD_VALUE = 'workloadValue';
    const FIELD_WORKLOAD_TIME = 'workloadTime';

    public function transform(mixed $value): ?array
    {
        $transformed = [self::FIELD_WORKLOAD_VALUE => null, self::FIELD_WORKLOAD_TIME => null];
        if (is_string($value)) {
            list($transformed[self::FIELD_WORKLOAD_VALUE], $transformed[self::FIELD_WORKLOAD_TIME]) = explode(' ', $value);
        }
        return $transformed;
    }

    public function reverseTransform(mixed $value): ?string
    {
        if (null === $value) return null;
        if (!\is_array($value)) throw new TransformationFailedException('Expected an array.');
        if (empty($value[self::FIELD_WORKLOAD_VALUE]) || empty($value[self::FIELD_WORKLOAD_TIME])) return null;
        return $value[self::FIELD_WORKLOAD_VALUE] . ' ' . $value[self::FIELD_WORKLOAD_TIME];
    }
}
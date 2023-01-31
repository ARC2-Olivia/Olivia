<?php

namespace App\Exception;

use App\Entity\EvaluationEvaluator;

class UnsupportedEvaluationEvaluatorTypeException extends \Exception
{
    public static function withDefaultMessage(int $code = 0, ?\Throwable $previous = null): self
    {
        $message = sprintf('The following evaluation evaluator types are currently supported: %s.', implode(', ', EvaluationEvaluator::getSupportedEvaluationEvaluatorTypes()));
        return new UnsupportedEvaluationEvaluatorTypeException($message, $code, $previous);
    }
}
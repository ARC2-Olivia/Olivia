<?php

namespace App\Exception;

use App\Entity\PracticalSubmoduleProcessor;

class UnsupportedEvaluationEvaluatorTypeException extends \Exception
{
    public static function withDefaultMessage(int $code = 0, ?\Throwable $previous = null): self
    {
        $message = sprintf('The following evaluation evaluator types are currently supported: %s.', implode(', ', PracticalSubmoduleProcessor::getSupportedProcessorTypes()));
        return new UnsupportedEvaluationEvaluatorTypeException($message, $code, $previous);
    }
}
<?php

namespace App\Exception;

use App\Entity\Lesson;
use App\Entity\PracticalSubmoduleQuestion;

class InvalidPracticalSubmoduleQuestionTypeException extends \Exception
{
    public static function forType(string $type, string $invalidType, int $code = 0, ?\Throwable $previous = null): self
    {
        $message = sprintf('Expected practical submodule question of type \'%s\'. Got \'%s\'.', $type, $invalidType);
        return new InvalidPracticalSubmoduleQuestionTypeException($message, $code, $previous);
    }

    public static function forMultiChoiceType(string $invalidType, int $code = 0, ?\Throwable $previous = null): self
    {
        return self::forType(PracticalSubmoduleQuestion::TYPE_MULTI_CHOICE, $invalidType, $code, $previous);
    }
}
<?php

namespace App\Exception;

use App\Entity\Lesson;

class UnsupportedLessonTypeException extends \Exception
{
    public static function withDefaultMessage(int $code = 0, ?\Throwable $previous = null): self
    {
        $message = sprintf('The following lesson types are currently supported: %s.', implode(', ', Lesson::getSupportedLessonTypes()));
        return new UnsupportedLessonTypeException($message, $code, $previous);
    }
}
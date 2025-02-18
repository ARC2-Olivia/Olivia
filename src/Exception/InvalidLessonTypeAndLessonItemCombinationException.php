<?php

namespace App\Exception;

use App\Entity\Lesson;

class InvalidLessonTypeAndLessonItemCombinationException extends \Exception
{
    public static function forLessonType(string $lessonType, string $invalidLessonItemClass, int $code = 0, ?\Throwable $previous = null): self
    {
        $message = sprintf('Lesson of type \'%s\' is being used with the %s object', $lessonType, $invalidLessonItemClass);
        return new InvalidLessonTypeAndLessonItemCombinationException($message, $code, $previous);
    }

    public static function forTextLessonType(string $invalidLessonItemClass, int $code = 0, ?\Throwable $previous = null): self
    {
        return self::forLessonType(Lesson::TYPE_TEXT, $invalidLessonItemClass, $code, $previous);
    }

    public static function forFileLessonType(string $invalidLessonItemClass, int $code = 0, ?\Throwable $previous = null): self
    {
        return self::forLessonType(Lesson::TYPE_FILE, $invalidLessonItemClass, $code, $previous);
    }

    public static function forVideoLessonType(string $invalidLessonItemClass, int $code = 0, ?\Throwable $previous = null): self
    {
        return self::forLessonType(Lesson::TYPE_VIDEO, $invalidLessonItemClass, $code, $previous);
    }

    public static function forQuizLessonType(string $invalidLessonItemClass, int $code = 0, ?\Throwable $previous = null): self
    {
        return self::forLessonType(Lesson::TYPE_QUIZ, $invalidLessonItemClass, $code, $previous);
    }
}
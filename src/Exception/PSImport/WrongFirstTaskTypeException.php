<?php

namespace App\Exception\PSImport;

class WrongFirstTaskTypeException extends \Exception
{
    public static function withDefaultTranslationKey(int $code = 0, ?\Throwable $previous = null): self
    {
        return new WrongFirstTaskTypeException('error.practicalSubmodule.import.wrongFirstTaskType', $code, $previous);
    }
}
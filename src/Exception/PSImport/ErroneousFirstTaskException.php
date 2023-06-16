<?php

namespace App\Exception\PSImport;

class ErroneousFirstTaskException extends \Exception
{
    public static function withDefaultTranslationKey(int $code = 0, ?\Throwable $previous = null): self
    {
        return new ErroneousFirstTaskException('error.practicalSubmodule.import.erroneousFirstTask', $code, $previous);
    }
}
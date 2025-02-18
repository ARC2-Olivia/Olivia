<?php

namespace App\Exception\PSImport;

class MissingTaskOrderKeyException extends \Exception
{
    public static function withDefaultTranslationKey(int $code = 0, ?\Throwable $previous = null): self
    {
        return new MissingTaskOrderKeyException('error.practicalSubmodule.import.missingTaskOrderKey', $code, $previous);
    }
}
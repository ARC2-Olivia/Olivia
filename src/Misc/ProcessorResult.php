<?php

namespace App\Misc;

use App\Entity\File;

class ProcessorResult
{
    private ?string $text;
    private ?File $file;

    public function __construct(?string $text = null, ?File $file = null)
    {
        $this->text = $text;
        $this->file = $file;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function isTextSet(): bool
    {
        return null !== $this->text;
    }

    public function isFileSet(): bool
    {
        return null !== $this->file;
    }
}
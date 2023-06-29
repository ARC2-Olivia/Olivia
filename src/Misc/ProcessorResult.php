<?php

namespace App\Misc;

use App\Entity\File;
use Doctrine\Common\Collections\Collection;

class ProcessorResult
{
    private ?string $text;

    /** @var File[]|null */
    private ?array $files;

    /**
     * @param string|null $text
     * @param File[]|null $files
     */
    public function __construct(?string $text = null, ?array $files = null)
    {
        $this->text = $text;
        $this->files = $files;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getFiles(): ?array
    {
        return $this->files;
    }

    public function isTextSet(): bool
    {
        return null !== $this->text;
    }

    public function areFilesSet(): bool
    {
        return null !== $this->files && count($this->files) > 0;
    }
}
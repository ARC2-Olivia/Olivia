<?php

namespace App\Misc;

use App\Entity\File;
use App\Entity\PracticalSubmoduleQuestion;

class ProcessorResult
{
    private ?string $text;

    /** @var File[]|null */
    private ?array $files;

    private bool $isHtml;

    private ?PracticalSubmoduleQuestion $question;

    /**
     * @param string|null $text
     * @param File[]|null $files
     */
    public function __construct(?string $text = null, ?array $files = null, bool $isHtml = false, ?PracticalSubmoduleQuestion $question = null)
    {
        $this->text = $text;
        $this->files = $files;
        $this->isHtml = $isHtml;
        $this->question = $question;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getFiles(): ?array
    {
        return $this->files;
    }

    public function isHtml(): ?bool
    {
        return $this->isHtml;
    }

    public function getQuestion(): ?PracticalSubmoduleQuestion
    {
        return $this->question;
    }

    public function isTextSet(): bool
    {
        return null !== $this->text;
    }

    public function areFilesSet(): bool
    {
        return null !== $this->files && count($this->files) > 0;
    }

    public function isQuestionSet(): bool
    {
        return null !== $this->question;
    }
}
<?php

namespace App\Misc;

use App\Entity\File;
use App\Entity\PracticalSubmoduleProcessorGroup;
use App\Entity\PracticalSubmoduleQuestion;

class ProcessorResult
{
    private ?string $text;
    /** @var File[]|null */ private ?array $files;
    private bool $isHtml;
    private ?PracticalSubmoduleQuestion $question;
    private ?PracticalSubmoduleProcessorGroup $processorGroup;
    private ?string $exportTag;
    private ?bool $isMultiValueProcessor;

    /**
     * @param string|null $text
     * @param File[]|null $files
     * @param bool $isHtml
     * @param PracticalSubmoduleQuestion|null $question
     * @param PracticalSubmoduleProcessorGroup|null $processorGroup
     * @param string|null $exportTag
     */
    public function __construct(?string $text = null,
                                ?array $files = null,
                                bool $isHtml = false,
                                ?PracticalSubmoduleQuestion $question = null,
                                ?PracticalSubmoduleProcessorGroup $processorGroup = null,
                                ?string $exportTag = null,
                                bool $isMultiValueProcessor = false
    )
    {
        $this->text = $text;
        $this->files = $files;
        $this->isHtml = $isHtml;
        $this->question = $question;
        $this->processorGroup = $processorGroup;
        $this->exportTag = $exportTag;
        $this->isMultiValueProcessor = $isMultiValueProcessor;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getDisplayableText(): ?string
    {
        if (null === $this->text) {
            return null;
        }
        return str_replace('|distinguish', '', $this->text);
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

    public function getProcessorGroup(): ?PracticalSubmoduleProcessorGroup
    {
        return $this->processorGroup;
    }

    public function getExportTag(): ?string
    {
        return $this->exportTag;
    }

    public function isMultiValueProcessor(): bool
    {
        return $this->isMultiValueProcessor;
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
<?php

namespace App\Misc;

use App\Entity\File;
use App\Entity\PracticalSubmoduleProcessorGroup;
use App\Entity\PracticalSubmoduleQuestion;

class ProcessorResult
{
    private ?string $text;
    private bool $isHtml;
    private ?PracticalSubmoduleQuestion $question;
    private ?string $exportTag;
    private ?bool $isMultiValueProcessor;

    /**
     * @param string|null $text
     * @param bool $isHtml
     * @param PracticalSubmoduleQuestion|null $question
     * @param string|null $exportTag
     * @param bool $isMultiValueProcessor
     */
    public function __construct(?string $text = null,
                                bool $isHtml = false,
                                ?PracticalSubmoduleQuestion $question = null,
                                ?string $exportTag = null,
                                bool $isMultiValueProcessor = false
    )
    {
        $this->text = $text;
        $this->isHtml = $isHtml;
        $this->question = $question;
        $this->exportTag = $exportTag;
        $this->isMultiValueProcessor = $isMultiValueProcessor;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function getSanitizedText(): ?string
    {
        if (null === $this->text) {
            return null;
        }
        return htmlspecialchars($this->text, ENT_QUOTES);
    }

    public function getDisplayableText(): ?string
    {
        if (null === $this->text) {
            return null;
        }
        $displayableText = str_replace('|distinguish', '', $this->text);
        $displayableText = str_replace('/*/', '<br/>', $displayableText);
        $displayableText = str_replace('|*|', '; ', $displayableText);
        return $displayableText;
    }

    public function isHtml(): ?bool
    {
        return $this->isHtml;
    }

    public function getQuestion(): ?PracticalSubmoduleQuestion
    {
        return $this->question;
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

    public function isQuestionSet(): bool
    {
        return null !== $this->question;
    }
}
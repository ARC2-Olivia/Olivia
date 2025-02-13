<?php

namespace App\Twig\Runtime;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class AccessibilityRuntime implements RuntimeExtensionInterface
{
    private const SESSION_DYSLEXIA_MODE = 'accessibility.dyslexiaMode';
    private const SESSION_CONTRAST_MODE = 'accessibility.contrastMode';

    private ?RequestStack $requestStack = null;

    private ?bool $dyslexiaMode = null;
    private ?bool $contrastMode = null;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getAccessibilityBodyClass(): string
    {
        $classes = [];
        if ($this->isDyslexiaModeEnabled()) $classes[] = 'dyslexic';
        if ($this->isContrastModeEnabled()) $classes[] = 'contrasted';
        return implode(' ', $classes);
    }

    public function isDyslexiaModeEnabled(): bool
    {
        if (null !== $this->dyslexiaMode) {
            return $this->dyslexiaMode;
        }
        $session = $this->requestStack->getSession();
        $this->dyslexiaMode = true === $session->get(self::SESSION_DYSLEXIA_MODE);
        return true === $this->dyslexiaMode;
    }

    public function isContrastModeEnabled(): bool
    {
        if (null !== $this->contrastMode) {
            return $this->contrastMode;
        }
        $session = $this->requestStack->getSession();
        $this->contrastMode = true === $session->get(self::SESSION_CONTRAST_MODE);
        return true === $this->contrastMode;
    }
}
<?php

namespace App\Twig\Runtime;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class AccessibilityRuntime implements RuntimeExtensionInterface
{
    private const SESSION_DYSLEXIA_MODE = 'accessibility.dyslexiaMode';

    private ?RequestStack $requestStack = null;

    private ?bool $dyslexiaMode = null;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
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
}
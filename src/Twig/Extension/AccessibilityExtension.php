<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\AccessibilityRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AccessibilityExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_dyslexia_mode_enabled', [AccessibilityRuntime::class, 'isDyslexiaModeEnabled']),
            new TwigFunction('is_contrast_mode_enabled', [AccessibilityRuntime::class, 'isContrastModeEnabled']),
            new TwigFunction('accessibility_body_class', [AccessibilityRuntime::class, 'getAccessibilityBodyClass'])
        ];
    }
}
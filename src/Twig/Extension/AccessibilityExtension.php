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
            new TwigFunction('is_dylexia_mode_enabled', [AccessibilityRuntime::class, 'isDyslexiaModeEnabled'])
        ];
    }
}
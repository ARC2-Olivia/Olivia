<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\GdprRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class GdprExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('gdpr_accepted', [GdprRuntime::class, 'isGdprAccepted']),
        ];
    }
}

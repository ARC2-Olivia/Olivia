<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\TrophyRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TrophyExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('play_trophy_animation', [TrophyRuntime::class, 'playTrophyAnimation']),
            new TwigFunction('play_golden_trophy_animation', [TrophyRuntime::class, 'playGoldenTrophyAnimation'])
        ];
    }
}

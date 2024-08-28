<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\OliviaRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class OliviaExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('translate_workload', [OliviaRuntime::class, 'translateWorkload']),
            new TwigFilter('is_youtube_video', [OliviaRuntime::class, 'isYoutubeVideo']),
            new TwigFilter('youtube_embed_link', [OliviaRuntime::class, 'getYoutubeEmbedLink']),
            new TwigFilter('is_valid_evaluator', [OliviaRuntime::class, 'isValidEvaluator']),
            new TwigFilter('txt2html', [OliviaRuntime::class, 'textToHtml']),
            new TwigFilter('make_pairs', [OliviaRuntime::class, 'makePairs'])
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('is_enrolled', [OliviaRuntime::class, 'isEnrolled']),
            new TwigFunction('is_user', [OliviaRuntime::class, 'isUser']),
            new TwigFunction('is_passed', [OliviaRuntime::class, 'isPassed']),
            new TwigFunction('txt2html', [OliviaRuntime::class, 'textToHtml']),
            new TwigFunction('make_pairs', [OliviaRuntime::class, 'makePairs'])
        ];
    }
}
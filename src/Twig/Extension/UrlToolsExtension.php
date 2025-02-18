<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\UrlToolsRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class UrlToolsExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('url_decode', [UrlToolsRuntime::class, 'urlDecode']),
            new TwigFunction('to_https', [UrlToolsRuntime::class, 'toHttps'])
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('url_decode', [UrlToolsRuntime::class, 'urlDecode']),
            new TwigFilter('to_https', [UrlToolsRuntime::class, 'toHttps']),
        ];
    }
}

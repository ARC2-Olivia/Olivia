<?php

declare(strict_types=1);

namespace App\Twig\Extension;

use App\Twig\Runtime\UrlDecodeRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class UrlDecodeExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [new TwigFunction('url_decode', [UrlDecodeRuntime::class, 'urlDecode'])];
    }

    public function getFilters(): array
    {
        return [new TwigFilter('url_decode', [UrlDecodeRuntime::class, 'urlDecode'])];
    }
}

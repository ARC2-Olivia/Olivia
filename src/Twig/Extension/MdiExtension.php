<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\MdiRuntime;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MdiExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [new TwigFilter('mdi', [MdiRuntime::class, 'mdi'])];
    }

    public function getFunctions()
    {
        return [new TwigFunction('mdi', [MdiRuntime::class, 'mdi'])];
    }
}
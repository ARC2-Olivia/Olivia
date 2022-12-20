<?php

namespace App\Twig;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MdiExtension extends AbstractExtension
{
    private ?Environment $twig = null;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getFilters()
    {
        return [new TwigFilter('mdi', [$this, 'mdi'])];
    }

    public function getFunctions()
    {
        return [new TwigFunction('mdi', [$this, 'mdi'])];
    }

    public function mdi(string $mdiName, string $mdiClass = "", string $mdiViewBox = "0 0 24 24"): Markup
    {
        try {
            return new Markup($this->twig->render('mdi/' . $mdiName . '.html.twig', ['class' => $mdiClass, 'viewBox' => $mdiViewBox]), 'UTF-8');
        } catch (\Exception $ex) {
            return new Markup($this->twig->render('mdi/exclamation.html.twig', ['class' => $mdiClass, 'viewBox' => $mdiViewBox]), 'UTF-8');
        }
    }
}
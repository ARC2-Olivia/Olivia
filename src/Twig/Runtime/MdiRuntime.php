<?php

namespace App\Twig\Runtime;

use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

class MdiRuntime implements RuntimeExtensionInterface
{
    private ?Environment $twig = null;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
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
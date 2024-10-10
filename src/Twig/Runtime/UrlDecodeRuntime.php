<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class UrlDecodeRuntime implements RuntimeExtensionInterface
{
    public function urlDecode(string $url): string
    {
        return urldecode($url);
    }
}
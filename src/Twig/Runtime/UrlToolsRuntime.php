<?php

namespace App\Twig\Runtime;

use Twig\Extension\RuntimeExtensionInterface;

class UrlToolsRuntime implements RuntimeExtensionInterface
{
    public function urlDecode(string $url): string
    {
        return urldecode($url);
    }

    public function toHttps(string $url): string
    {
        return str_replace('http://', 'https://', $url);
    }
}
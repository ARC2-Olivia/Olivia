<?php

namespace App\Service;

class SanitizerService
{
    public function sanitizeText(?string $string): ?string
    {
        if (null !== $string) {
            $string = htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
        }

        return $string;
    }

    public function unsanitizeText(?string $string): ?string
    {
        if (null !== $string) {
            $string = htmlspecialchars_decode($string, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
        }

        return $string;
    }

    public function sanitizeHtml(?string $string): ?string
    {
        if (null !== $string) {
            $allowed = ['p', 'a', 'img', 'br', 'span', 'ul', 'ol', 'li', 'b', 'i', 'u', 's', 'em', 'strong', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
            $string = strip_tags($string, $allowed);
        }

        return $string;
    }
}
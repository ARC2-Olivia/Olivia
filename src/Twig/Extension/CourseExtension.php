<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\CourseRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CourseExtension extends AbstractExtension
{

    public function getFunctions(): array
    {
        return [
            new TwigFunction('certificate_not_received_text', [CourseRuntime::class, 'certificateNotReceivedText']),
            new TwigFunction('certificate_received_text', [CourseRuntime::class, 'certificateReceivedText'])
        ];
    }
}

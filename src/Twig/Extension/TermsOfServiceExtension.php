<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\TermsOfServiceRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TermsOfServiceExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('terms_of_service_accepted', [TermsOfServiceRuntime::class, 'isTermsOfServiceAccepted']),
        ];
    }
}

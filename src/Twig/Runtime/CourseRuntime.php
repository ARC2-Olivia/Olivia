<?php

namespace App\Twig\Runtime;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class CourseRuntime implements RuntimeExtensionInterface
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function certificateNotReceivedText(): ?string
    {
        $number = random_int(1, 2);
        return $this->translator->trans('course.certificate.notReceived.'.$number, [], 'app');
    }

    public function certificateReceivedText(): ?string
    {
        $number = random_int(1, 2);
        return $this->translator->trans('course.certificate.received.'.$number, [], 'app');
    }
}
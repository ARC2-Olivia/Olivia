<?php

namespace App\Twig\Runtime;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\RuntimeExtensionInterface;

class TrophyRuntime implements RuntimeExtensionInterface
{
    private ?RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function playTrophyAnimation(): bool
    {
        return $this->checkAndRemoveSessionVariable('play_trophy_animation');
    }

    public function playGoldenTrophyAnimation(): bool
    {
        return $this->checkAndRemoveSessionVariable('play_golden_trophy_animation');
    }

    private function checkAndRemoveSessionVariable(string $sessionVar): bool
    {
        if ($this->requestStack->getSession()->has($sessionVar) && $this->requestStack->getSession()->get($sessionVar) === true) {
            $this->requestStack->getSession()->remove($sessionVar);
            return true;
        }
        return false;
    }
}
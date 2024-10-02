<?php

namespace App\Twig\Runtime;

use App\Entity\User;
use App\Service\EnrollmentService;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\Markup;

class TrophyRuntime implements RuntimeExtensionInterface
{
    private ?RequestStack $requestStack = null;
    private ?Environment $twig = null;
    private ?EnrollmentService $enrollmentService = null;

    public function __construct(RequestStack $requestStack, Environment $twig, EnrollmentService $enrollmentService)
    {
        $this->requestStack = $requestStack;
        $this->twig = $twig;
        $this->enrollmentService = $enrollmentService;
    }

    public function playTrophyAnimation(): bool
    {
        return $this->checkAndRemoveSessionVariable('play_trophy_animation');
    }

    public function playGoldenTrophyAnimation(): bool
    {
        return $this->checkAndRemoveSessionVariable('play_golden_trophy_animation');
    }

    public function getTrophyIcon(User $user, string $class = ''): ?Markup
    {
        if (null !== $user->getAllCoursesPassedAt())
            return new Markup($this->twig->render('mdi/trophy-variant.html.twig', ['class' => 'fg-yellow '.$class, 'viewBox' => '0 0 24 24']), 'UTF-8');
        if ($this->enrollmentService->countPassedByUser($user) > 0)
            return new Markup($this->twig->render('mdi/trophy-variant.html.twig', ['class' => 'fg-orange '.$class, 'viewBox' => '0 0 24 24']), 'UTF-8');
        return null;
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
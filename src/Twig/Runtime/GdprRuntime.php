<?php

namespace App\Twig\Runtime;

use App\Entity\Gdpr;
use App\Entity\User;
use App\Service\GdprService;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\RuntimeExtensionInterface;

class GdprRuntime implements RuntimeExtensionInterface
{
    private ?Security $security = null;
    private ?GdprService $gdprService = null;

    public function __construct(Security $security, GdprService $gdprService)
    {
        $this->security = $security;
        $this->gdprService = $gdprService;
    }

    public function isGdprAccepted(Gdpr $gdpr): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return $this->gdprService->userAcceptedGdpr($user, $gdpr);
    }
}
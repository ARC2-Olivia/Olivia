<?php

namespace App\Twig\Runtime;

use App\Entity\TermsOfService;
use App\Entity\User;
use App\Service\TermsOfServiceService;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\RuntimeExtensionInterface;

class TermsOfServiceRuntime implements RuntimeExtensionInterface
{
    private ?Security $security = null;
    private ?TermsOfServiceService $termsOfServiceService = null;

    public function __construct(Security $security, TermsOfServiceService $termsOfServiceService)
    {
        $this->security = $security;
        $this->termsOfServiceService = $termsOfServiceService;
    }

    public function isTermsOfServiceAccepted(TermsOfService $termsOfService): bool
    {
        /** @var User $user */
        $user = $this->security->getUser();
        return $this->termsOfServiceService->userAcceptedTermsOfService($user, $termsOfService);
    }
}
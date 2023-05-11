<?php

namespace App\Security;

use App\Entity\TermsOfService;
use App\Entity\User;
use App\Service\TermsOfServiceService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class TermsOfServiceVoter extends Voter
{
    public const EDIT = 'tos_edit';
    public const ACCEPT = 'tos_accept';
    public const RESCIND = 'tos_rescind';

    private ?Security $security = null;
    private ?TermsOfServiceService $termsOfServiceService = null;

    public function __construct(Security $security, TermsOfServiceService $termsOfServiceService)
    {
        $this->security = $security;
        $this->termsOfServiceService = $termsOfServiceService;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof TermsOfService && in_array($attribute, [self::EDIT, self::ACCEPT, self::RESCIND]);
    }

    /**
     * @param string $attribute
     * @param TermsOfService $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) return false;

        switch ($attribute) {
            case self::EDIT: return $this->canEdit($subject);
            case self::ACCEPT: return $this->canAccept($subject, $user);
            case self::RESCIND: return $this->canRescind($subject, $user);
        }

        return false;
    }

    private function canEdit(TermsOfService $termsOfService)
    {
        return $this->security->isGranted(User::ROLE_USER) && $termsOfService->isActive();
    }

    private function canAccept(TermsOfService $termsOfService, User $user)
    {
        return $this->isRegularUser() && $termsOfService->isActive() && !$this->termsOfServiceService->userAcceptedTermsOfService($user, $termsOfService);
    }

    private function canRescind(TermsOfService $termsOfService, User $user)
    {
        return $this->isRegularUser() && $this->termsOfServiceService->userAcceptedTermsOfService($user, $termsOfService);
    }

    private function isRegularUser()
    {
        return $this->security->isGranted(User::ROLE_USER) && !$this->security->isGranted('ROLE_ADMIN') && !$this->security->isGranted('ROLE_MODERATOR');
    }
}

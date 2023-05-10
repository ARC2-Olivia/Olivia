<?php

namespace App\Security;

use App\Entity\TermsOfService;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class TermsOfServiceVoter extends Voter
{
    public const EDIT = 'tos_edit';

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof TermsOfService && $attribute === self::EDIT;
    }

    /**
     * @param string $attribute
     * @param TermsOfService $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) return false;
        if ($attribute === self::EDIT) return $this->canEdit($subject, $user);
        return false;
    }

    private function canEdit(TermsOfService $termsOfService, UserInterface $user)
    {
        return in_array(User::ROLE_ADMIN, $user->getRoles()) && $termsOfService->isActive();
    }
}

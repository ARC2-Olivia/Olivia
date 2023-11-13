<?php

namespace App\Security;

use App\Entity\Gdpr;
use App\Entity\User;
use App\Service\GdprService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class GdprVoter extends Voter
{
    public const EDIT = 'gdpr_edit';
    public const ACCEPT = 'gdpr_accept';
    public const RESCIND = 'gdpr_rescind';

    private ?Security $security = null;
    private ?GdprService $gdprService = null;

    public function __construct(Security $security, GdprService $gdprService)
    {
        $this->security = $security;
        $this->gdprService = $gdprService;
    }

    protected function supports(string $attribute, $subject): bool
    {
        return $subject instanceof Gdpr && in_array($attribute, [self::EDIT, self::ACCEPT, self::RESCIND]);
    }

    /**
     * @param string $attribute
     * @param Gdpr $subject
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

    private function canEdit(Gdpr $gdpr): bool
    {
        return $this->security->isGranted(User::ROLE_USER) && $gdpr->isActive();
    }

    private function canAccept(Gdpr $gdpr, User $user): bool
    {
        return $this->isRegularUser() && $gdpr->isActive() && !$this->gdprService->userAcceptedGdpr($user, $gdpr);
    }

    private function canRescind(Gdpr $gdpr, User $user): bool
    {
        return $this->isRegularUser() && $this->gdprService->userAcceptedGdpr($user, $gdpr);
    }

    private function isRegularUser(): bool
    {
        return $this->security->isGranted(User::ROLE_USER) && !$this->security->isGranted('ROLE_ADMIN') && !$this->security->isGranted('ROLE_MODERATOR');
    }
}

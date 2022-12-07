<?php

namespace App\Security;

use App\Entity\Course;
use App\Entity\User;
use App\Service\EnrollmentService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class CourseVoter extends Voter
{
    const ENROLL = 'enroll';
    const VIEW = 'view';

    private ?Security $security = null;
    private ?EnrollmentService $enrollmentService = null;

    public function __construct(Security $security, EnrollmentService $enrollmentService)
    {
        $this->security = $security;
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * @param string $attribute
     * @param Course $subject
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Course && in_array($attribute, [self::ENROLL, self::VIEW]);
    }

    /**
     * @param string $attribute
     * @param Course $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        return match ($attribute) {
            self::ENROLL => $this->canEnroll($subject, $user),
            self::VIEW => $this->canView($subject, $user),
            default => false
        };
    }

    private function canEnroll(Course $course, User $user): bool
    {
        return $this->security->isGranted('ROLE_USER')
            && !$this->security->isGranted('ROLE_MODERATOR')
            && !$this->security->isGranted('ROLE_ADMIN')
            && !$this->enrollmentService->isEnrolled($course, $user)
        ;
    }

    private function canView(Course $course, User $user): bool
    {
        return $this->security->isGranted('ROLE_MODERATOR')
            || ($this->security->isGranted('ROLE_USER') && $this->enrollmentService->isEnrolled($course, $user));
    }

}
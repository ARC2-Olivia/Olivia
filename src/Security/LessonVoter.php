<?php

namespace App\Security;

use App\Entity\Lesson;
use App\Entity\User;
use App\Service\EnrollmentService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

class LessonVoter extends Voter
{
    const VIEW = 'view';
    const ADD_QUIZ_QUESTION = 'add_quiz_question';

    private ?Security $security = null;
    private ?EnrollmentService $enrollmentService = null;

    public function __construct(Security $security, EnrollmentService $enrollmentService)
    {
        $this->security = $security;
        $this->enrollmentService = $enrollmentService;
    }

    /**
     * @param string $attribute
     * @param Lesson $subject
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Lesson && in_array($attribute, [self::VIEW, self::ADD_QUIZ_QUESTION]);
    }

    /**
     * @param string $attribute
     * @param Lesson $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof User) return false;

        if ($attribute === self::VIEW) {
            return $this->canView($subject, $user);
        }

        if ($attribute === self::ADD_QUIZ_QUESTION) {
            return $this->canAddQuizQuestion($subject, $user);
        }

        return false;
    }

    private function canView(Lesson $lesson, User $user): bool
    {
        return $this->security->isGranted('ROLE_MODERATOR')
            || ($this->security->isGranted('ROLE_USER') && $this->enrollmentService->isEnrolled($lesson->getCourse(), $user));
    }

    private function canAddQuizQuestion(Lesson $lesson, User $user): bool
    {
        return $this->security->isGranted('ROLE_MODERATOR') && $lesson->getType() === Lesson::TYPE_QUIZ;
    }

}
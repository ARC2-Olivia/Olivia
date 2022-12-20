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
    const SOLVE_QUIZ = 'solve_quiz';

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
        return $subject instanceof Lesson && in_array($attribute, [self::VIEW, self::ADD_QUIZ_QUESTION, self::SOLVE_QUIZ]);
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

        return match ($attribute) {
            self::VIEW => $this->canView($subject, $user),
            self::ADD_QUIZ_QUESTION => $this->canAddQuizQuestion($subject),
            self::SOLVE_QUIZ => $this->canSolveQuiz($subject, $user),
            default => false
        };
    }

    private function canView(Lesson $lesson, User $user): bool
    {
        return $this->security->isGranted('ROLE_MODERATOR')
            || ($this->security->isGranted('ROLE_USER') && $this->enrollmentService->isEnrolled($lesson->getCourse(), $user));
    }

    private function canAddQuizQuestion(Lesson $lesson): bool
    {
        return $this->security->isGranted('ROLE_MODERATOR') && $lesson->getType() === Lesson::TYPE_QUIZ;
    }

    private function canSolveQuiz(Lesson $lesson, User $user): bool
    {
        return $this->security->isGranted('ROLE_USER')
            && !$this->security->isGranted('ROLE_MODERATOR')
            && $this->enrollmentService->isEnrolled($lesson->getCourse(), $user);
    }

}
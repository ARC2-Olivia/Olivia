<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\LessonCompletion;
use App\Entity\LessonItemQuiz;
use App\Entity\QuizQuestionAnswer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LessonService
{
    private ?EntityManagerInterface $em = null;
    private ?UrlGeneratorInterface $urlGenerator = null;

    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $urlGenerator)
    {
        $this->em = $em;
        $this->urlGenerator = $urlGenerator;
    }

    public function getLessonsInfo(Course $course, User $user): array
    {
        $lessonRepository = $this->em->getRepository(Lesson::class);
        $lessonCompletionRepository = $this->em->getRepository(LessonCompletion::class);
        $lessons = $lessonRepository->findAllForCourseSortedByPosition($course);
        $lessonsInfo = [];
        foreach ($lessons as $lesson) {
            $lessonCompletion = $lessonCompletionRepository->findOneBy(['lesson' => $lesson, 'user' => $user]);
            $lessonsInfo[] = [
                'lesson' => $lesson,
                'completed' => $lessonCompletion !== null ? $lessonCompletion->isCompleted() : false,
                'showUrl' => $this->urlGenerator->generate('lesson_show', ['lesson' => $lesson->getId()]),
                'toggleUrl' => $this->urlGenerator->generate('lesson_toggle_completed', ['lesson' => $lesson->getId()])
            ];
        }
        return $lessonsInfo;
    }

    public function hasCompletionData(Lesson $lesson, User $user): bool
    {
        return $this->em->getRepository(LessonCompletion::class)->findOneBy(['lesson' => $lesson, 'user' => $user]);
    }

    public function getQuizPercentage(Lesson $lesson, User $user): ?int
    {
        if ($lesson->getType() !== Lesson::TYPE_QUIZ) return null;

        $lessonItemQuiz = $this->em->getRepository(LessonItemQuiz::class)->findOneBy(['lesson' => $lesson]);
        if ($lessonItemQuiz === null) return null;

        $quizQuestionAnswerRepository = $this->em->getRepository(QuizQuestionAnswer::class);
        $sum = 0;
        $count = 0;
        foreach ($lessonItemQuiz->getQuizQuestions() as $quizQuestion) {
            $quizQuestionAnswer = $quizQuestionAnswerRepository->findOneBy(['question' => $quizQuestion, 'user' => $user]);
            if ($quizQuestionAnswer !== null && $quizQuestionAnswer->getAnswer() === $quizQuestion->getCorrectAnswer()) {
                $sum += 100;
            }
            $count++;
        }
        return $sum / $count;
    }
}
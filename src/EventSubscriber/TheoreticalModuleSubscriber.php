<?php

namespace App\EventSubscriber;

use App\Entity\AllCoursesCompletedUser;
use App\Entity\Course;
use App\Entity\Enrollment;
use App\Entity\Lesson;
use App\Entity\LessonCompletion;
use App\Entity\LessonItemQuiz;
use App\Entity\QuizQuestion;
use App\Entity\QuizQuestionAnswer;
use App\Entity\User;
use App\Event\QuizFinishedEvent;
use App\Event\TheoreticalModuleCompletedEvent;
use App\Service\EnrollmentService;
use App\Service\LessonService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class TheoreticalModuleSubscriber implements EventSubscriberInterface
{
    public const PLAY_TROPHY_ANIMATION = 'play_trophy_animation';
    public const PLAY_GOLDEN_TROPHY_ANIMATION = 'play_golden_trophy_animation';

    private ?EntityManagerInterface $em = null;
    private ?LessonService $lessonService = null;
    private ?EnrollmentService $enrollmentService = null;
    private ?RequestStack $requestStack = null;
    private ?TranslatorInterface $translator = null;
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(EntityManagerInterface $em,
                                LessonService $lessonService,
                                EnrollmentService $enrollmentService,
                                RequestStack $requestStack,
                                TranslatorInterface $translator,
                                EventDispatcherInterface $eventDispatcher
    )
    {
        $this->em = $em;
        $this->lessonService = $lessonService;
        $this->enrollmentService = $enrollmentService;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            QuizFinishedEvent::class => ['onQuizFinished', 0],
            TheoreticalModuleCompletedEvent::class => ['onTheoreticalModuleCompleted', -1]
        ];
    }

    public function onQuizFinished(QuizFinishedEvent $event): void
    {
        $user = $event->getUser();
        $lesson = $event->getLesson();
        $lessonCompleted = false;
        $courseCompleted = false;

        if ($lesson::TYPE_QUIZ !== $lesson->getType()) {
            return;
        }

        $quizQuestionAnswers = [];
        $qqaRepository = $this->em->getRepository(QuizQuestionAnswer::class);

        $quiz = $this->em->getRepository(LessonItemQuiz::class)->findOneBy(['lesson' => $lesson]);
        $quizQuestions = $this->em->getRepository(QuizQuestion::class)->findBy(['quiz' => $quiz]);
        foreach ($quizQuestions as $quizQuestion) {
            $quizQuestionAnswers[] = $qqaRepository->findOneBy(['question' => $quizQuestion, 'user' => $user]);
        }

        $oldPercentage = $this->lessonService->getQuizPercentage($lesson, $user);
        $percentage = $this->calculatePercentage($quizQuestionAnswers);

        if ($percentage >= $oldPercentage) {
            $this->handleBetterScore($lesson, $user, $quiz, $percentage, $lessonCompleted);
            $this->requestStack->getSession()->getFlashBag()->add('success', $this->translator->trans('success.quiz.finish', ['%quizName%' => $lesson->getName()], 'message'));
        } else {
            $this->requestStack->getSession()->getFlashBag()->add('warning', $this->translator->trans('warning.lesson.worseQuizResult', [], 'message'));
        }

        if ($lessonCompleted) $this->handleCourseCompletion($lesson->getCourse(), $user, $courseCompleted);
        if ($courseCompleted) $this->handleTheoreticalModuleCompletion($user);
    }

    public function onTheoreticalModuleCompleted(TheoreticalModuleCompletedEvent $event): void
    {
        $user = $event->getUser();
        if (null !== $user->getAllCoursesCompletedUser()) {
            return;
        }
        $allCoursesCompletedUser = (new AllCoursesCompletedUser())->setUser($user)->setAt(new \DateTimeImmutable());
        $this->em->persist($allCoursesCompletedUser);
        $this->em->flush();
        $this->requestStack->getSession()->set(self::PLAY_GOLDEN_TROPHY_ANIMATION, true);
    }

    /**
     * @param array $quizQuestionAnswers
     * @return float|int
     */
    public function calculatePercentage(array $quizQuestionAnswers): int|float
    {
        $sum = 0;
        $questionCount = 0;
        foreach ($quizQuestionAnswers as $quizQuestionAnswer) {
            $condition = QuizQuestion::TYPE_TRUE_FALSE === $quizQuestionAnswer->getQuestion()->getType() && ((bool)$quizQuestionAnswer->getAnswer()) === $quizQuestionAnswer->getQuestion()->getCorrectAnswer();
            $condition = $condition || (QuizQuestion::TYPE_SINGLE_CHOICE === $quizQuestionAnswer->getQuestion()->getType() && ((int)$quizQuestionAnswer->getAnswer()) === $quizQuestionAnswer->getQuestion()->getCorrectChoiceId());
            if (true === $condition) $sum += 100;
            $questionCount++;
        }
        return $questionCount > 0 ? $sum / $questionCount : 0;
    }

    /**
     * @param Lesson|null $lesson
     * @param User|null $user
     * @param LessonItemQuiz $quiz
     * @param float|int $percentage
     * @return void
     */
    public function handleBetterScore(?Lesson $lesson, ?User $user, LessonItemQuiz $quiz, float|int $percentage, bool &$lessonCompleted = false): void
    {
        $lessonCompletion = $this->em->getRepository(LessonCompletion::class)->findOneBy(['lesson' => $lesson, 'user' => $user]);
        $persistCompletion = false;
        if ($lessonCompletion === null) {
            $lessonCompletion = (new LessonCompletion())->setLesson($lesson)->setUser($user);
            $persistCompletion = true;
        }

        $lessonCompletion->setCompleted($percentage >= $quiz->getPassingPercentage());
        if ($persistCompletion) {
            $this->em->persist($lessonCompletion);
            $this->em->flush();
        }

        $lessonCompleted = $lessonCompletion->isCompleted();
    }

    private function handleCourseCompletion(Course $course, User $user, bool &$courseCompleted = false): void
    {
        if ($this->enrollmentService->checkCoursePassingCondition($course, $user)) {
            $this->enrollmentService->markAsPassed($course, $user);
            $this->requestStack->getSession()->set(self::PLAY_TROPHY_ANIMATION, true);
            $courseCompleted = true;
        } else {
            $this->enrollmentService->markAsNotPassed($course, $user);
        }
    }

    private function handleTheoreticalModuleCompletion(User $user)
    {
        $totalCourseCount = $this->em->getRepository(Course::class)->count([]);
        $userCompletedCourseCount = $this->enrollmentService->countPassedByUser($user);
        if ($totalCourseCount === $userCompletedCourseCount) {
            $this->eventDispatcher->dispatch(new TheoreticalModuleCompletedEvent($user));
        }
    }
}
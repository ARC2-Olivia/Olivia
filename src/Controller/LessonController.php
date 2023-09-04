<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\LessonCompletion;
use App\Entity\LessonItemEmbeddedVideo;
use App\Entity\LessonItemFile;
use App\Entity\LessonItemQuiz;
use App\Entity\LessonItemText;
use App\Entity\Note;
use App\Entity\QuizQuestion;
use App\Entity\QuizQuestionAnswer;
use App\Entity\User;
use App\Form\LessonType;
use App\Form\Quiz\QuizType;
use App\Form\QuizQuestionType;
use App\Repository\LessonCompletionRepository;
use App\Repository\LessonRepository;
use App\Service\EnrollmentService;
use App\Service\LessonService;
use App\Service\NavigationService;
use App\Traits\BasicFileManagementTrait;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/{_locale}/lesson", name: "lesson_", requirements: ["_locale" => "%locale.supported%"])]
class LessonController extends BaseController
{
    use BasicFileManagementTrait;

    private ?LessonService $lessonService = null;
    private ?EnrollmentService $enrollmentService = null;
    private ?NavigationService $navigationService = null;

    public function __construct(EntityManagerInterface $em,
                                TranslatorInterface $translator,
                                LessonService $lessonService,
                                EnrollmentService $enrollmentService,
                                NavigationService $navigationService
    ) {
        $this->lessonService = $lessonService;
        $this->enrollmentService = $enrollmentService;
        $this->navigationService = $navigationService;
        parent::__construct($em, $translator);
    }

    #[Route("/course/{course}", name: "course")]
    #[IsGranted("view", subject: "course")]
    public function course(Course $course): Response
    {
        $lessonsInfo = $this->lessonService->getLessonsInfo($course, $this->getUser());
        return $this->render('lesson/course.html.twig', [
            'course' => $course,
            'lessonsInfo' => $lessonsInfo,
            'navigation' => $this->navigationService->forCourse($course, NavigationService::COURSE_LESSONS)
        ]);
    }

    #[Route("/course/{course}/new/{lessonType}", name: "new", defaults: ['lessonType' => Lesson::TYPE_TEXT])]
    #[IsGranted("ROLE_MODERATOR")]
    public function new(Course $course, string $lessonType, Request $request): Response
    {
        if (!in_array($lessonType, Lesson::getSupportedLessonTypes())) {
            $lessonType = Lesson::TYPE_TEXT;
        }
        $lesson = new Lesson();
        $form = $this->createForm(LessonType::class, $lesson, ['lesson_type' => $lessonType, 'include_translatable_fields' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository = $this->em->getRepository(Lesson::class);
            $lesson->setCourse($course);
            $lesson->setPosition($lessonRepository->nextPositionInCourse($course));
            $lessonRepository->save($lesson, true);
            $this->processLessonTranslation($lesson, $form);
            $recheckCoursePassingCondition = false;

            if ($lesson->getType() === Lesson::TYPE_TEXT) {
                $this->handleTextLessonType($form, $lesson);
            } else if ($lesson->getType() === Lesson::TYPE_FILE) {
                try {
                    $this->handleFileLessonType($form, $course, $lesson);
                } catch (\Exception $ex) {
                    $this->addFlash('error', $this->translator->trans('error.lesson.file.store', [], 'message'));
                    $this->em->remove($lesson);
                    $this->em->flush();
                    return $this->redirectToRoute('lesson_new', ['course' => $course->getId()]);
                }
            } else if ($lesson->getType() === Lesson::TYPE_VIDEO) {
                try {
                    $this->handleVideoLessonType($form, $lesson);
                } catch (\Exception $ex) {
                    $this->addFlash('error', $this->translator->trans('error.lesson.video.store', [], 'message'));
                    $this->em->remove($lesson);
                    $this->em->flush();
                    return $this->redirectToRoute('lesson_new', ['course' => $course->getId()]);
                }
            } else if ($lesson->getType() === Lesson::TYPE_QUIZ) {
                $this->handleQuizLessonType($form, $lesson);
            }

            $this->addFlash('success', $this->translator->trans('success.lesson.new', ['%course%' => $course->getName()], 'message'));
            return $this->redirectToRoute('lesson_course', ['course' => $course->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('lesson/new.html.twig', ['course' => $course, 'form' => $form->createView(), 'lessonType' => $lessonType]);
    }

    #[Route("/quiz/{lesson}/new-question", name: "new_quiz_question")]
    #[IsGranted("add_quiz_question", subject: "lesson")]
    public function newQuizQuestion(Lesson $lesson, Request $request): Response
    {
        $quizQuestion = new QuizQuestion();
        $form = $this->createForm(QuizQuestionType::class, $quizQuestion, ['include_translatable_field' => true]);

        $quiz = $this->em->getRepository(LessonItemQuiz::class)->findOneBy(['lesson' => $lesson]);
        if ($quiz !== null) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $quizQuestion->setQuiz($quiz);
                $this->em->persist($quizQuestion);
                $this->em->flush();
                $this->processQuizQuestionTranslation($form, $quizQuestion);
                $this->addFlash('success', $this->translator->trans('success.quizQuestion.new', [], 'message'));
                return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
            } else {
                foreach ($form->getErrors() as $error) {
                    $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
                }
            }
        } else {
            $this->addFlash('error', $this->translator->trans('error.lesson.quiz.type', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
        }

        $lessonsInfo = $this->lessonService->getLessonsInfo($lesson->getCourse(), $this->getUser());
        return $this->render('lesson/quiz/new.html.twig', ['lesson' => $lesson, 'lessonsInfo' => $lessonsInfo, 'form' => $form->createView()]);
    }

    #[Route("/show/{lesson}", name: "show")]
    #[IsGranted("view", subject: "lesson")]
    public function show(Lesson $lesson, LessonRepository $lessonRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var LessonItemText|LessonItemFile|LessonItemEmbeddedVideo|LessonItemQuiz|null $lessonItem */
        $lessonItem = match ($lesson->getType()) {
            Lesson::TYPE_TEXT => $this->em->getRepository(LessonItemText::class)->findOneBy(['lesson' => $lesson]),
            Lesson::TYPE_FILE => $this->em->getRepository(LessonItemFile::class)->findOneBy(['lesson' => $lesson]),
            Lesson::TYPE_VIDEO => $this->em->getRepository(LessonItemEmbeddedVideo::class)->findOneBy(['lesson' => $lesson]),
            Lesson::TYPE_QUIZ => $this->em->getRepository(LessonItemQuiz::class)->findOneBy(['lesson' => $lesson]),
            default => null,
        };

        $note = null;
        if ($this->isGranted('ROLE_USER') && !$this->isGranted('ROLE_MODERATOR') && !$this->isGranted('ROLE_ADMIN')) {
            $note = $this->em->getRepository(Note::class)->findOneBy(['lesson' => $lesson, 'user' => $user]);
        }

        $lessonCompletion = $this->em->getRepository(LessonCompletion::class)->findOneBy(['lesson' => $lesson, 'user' => $this->getUser()]);
        $lessonsInfo = $this->lessonService->getLessonsInfo($lesson->getCourse(), $user);
        $previousLesson = $lessonRepository->findPreviousLesson($lesson);
        $nextLesson = $lessonRepository->findNextLesson($lesson);
        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'lessonItem' => $lessonItem,
            'lessonCompletion' => $lessonCompletion,
            'lessonsInfo' => $lessonsInfo,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson,
            'note' => $note,
            'quizPercentage' => !$this->isGranted('ROLE_MODERATOR') ? $this->lessonService->getQuizPercentage($lesson, $user) : null,
            'navigation' => $this->navigationService->forCourse($lesson->getCourse(), NavigationService::COURSE_LESSONS)
        ]);
    }

    #[Route("/delete/{lesson}", name: "delete")]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(Lesson $lesson, Request $request): Response
    {
        $course = $lesson->getCourse();

        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('lesson.delete', $csrfToken)) {
            $lessonName = $lesson->getName();
            $recheckCoursePassingCondition = false;
            switch ($lesson->getType()) {
                case Lesson::TYPE_TEXT: {
                    $lessonItemText = $this->em->getRepository(LessonItemText::class)->findOneBy(['lesson' => $lesson]);
                    $this->em->remove($lessonItemText);
                    break;
                }
                case Lesson::TYPE_VIDEO: {
                    $lessonItemEmbeddedVideo = $this->em->getRepository(LessonItemEmbeddedVideo::class)->findOneBy(['lesson' => $lesson]);
                    $this->em->remove($lessonItemEmbeddedVideo);
                    break;
                }
                case Lesson::TYPE_FILE: {
                    $lessonItemFile = $this->em->getRepository(LessonItemFile::class)->findOneBy(['lesson' => $lesson]);
                    $uploadDir = $this->getParameter('dir.lesson_file');
                    $this->removeFile($uploadDir . '/' . $lessonItemFile->getFilename());
                    $this->em->remove($lessonItemFile);
                    break;
                }
                case Lesson::TYPE_QUIZ: {
                    $lessonItemQuiz = $this->em->getRepository(LessonItemQuiz::class)->findOneBy(['lesson' => $lesson]);
                    $this->em->remove($lessonItemQuiz);
                    $recheckCoursePassingCondition = true;
                    break;
                }
            }

            foreach ($this->em->getRepository(LessonCompletion::class)->findBy(['lesson' => $lesson]) as $lessonCompletion) {
                $this->em->remove($lessonCompletion);
            }

            $this->em->remove($lesson);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.lesson.delete', ['%lesson%' => $lessonName, '%course%' => $course->getName()], 'message'));

            if ($recheckCoursePassingCondition) {
                foreach ($course->getEnrollments() as $enrollment) {
                    $this->regrade($course, $enrollment->getUser());
                }
            }

            return $this->redirectToRoute('lesson_course', ['course' => $course->getId()]);
        }

        return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
    }

    #[Route("/edit/{lesson}", name: "edit")]
    #[IsGranted('ROLE_MODERATOR')]
    public function edit(Lesson $lesson, Request $request): Response
    {
        $lessonItem = match ($lesson->getType()) {
            Lesson::TYPE_TEXT => $this->em->getRepository(LessonItemText::class)->findOneBy(['lesson' => $lesson]),
            Lesson::TYPE_FILE => $this->em->getRepository(LessonItemFile::class)->findOneBy(['lesson' => $lesson]),
            Lesson::TYPE_VIDEO => $this->em->getRepository(LessonItemEmbeddedVideo::class)->findOneBy(['lesson' => $lesson]),
            Lesson::TYPE_QUIZ => $this->em->getRepository(LessonItemQuiz::class)->findOneBy(['lesson' => $lesson]),
            default => null
        };
        $form = $this->createForm(LessonType::class, $lesson, ['lesson_type' => $lesson->getType(), 'lesson_item' => $lessonItem]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            if ($lesson->getType() === Lesson::TYPE_TEXT) {
                $this->handleTextLessonType($form, $lesson, $lessonItem);
            } else if ($lesson->getType() === Lesson::TYPE_FILE) {
                try {
                    $this->handleFileLessonType($form, $lesson->getCourse(), $lesson, $lessonItem);
                } catch (\Exception $ex) {
                    $this->addFlash('error', $this->translator->trans('error.lesson.file.store', [], 'message'));
                    return $this->redirectToRoute('lesson_edit', ['lesson' => $lesson->getId()]);
                }
            } else if ($lesson->getType() === Lesson::TYPE_VIDEO) {
                try {
                    $this->handleVideoLessonType($form, $lesson, $lessonItem);
                } catch (\Exception $ex) {
                    $this->addFlash('error', $this->translator->trans('error.lesson.video.store', [], 'message'));
                    return $this->redirectToRoute('lesson_edit', ['lesson' => $lesson->getId()]);
                }
            } else if ($lesson->getType() === Lesson::TYPE_QUIZ) {
                $this->handleQuizLessonType($form, $lesson, $lessonItem);
            }

            $this->addFlash('success', $this->translator->trans('success.lesson.edit', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        $lessonsInfo = $this->lessonService->getLessonsInfo($lesson->getCourse(), $this->getUser());
        return $this->render('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'lessonItem' => $lessonItem,
            'lessonsInfo' => $lessonsInfo,
            'form' => $form->createView()
        ]);
    }

    #[Route("/quiz/{lesson}", name: "quiz", methods: ["POST"])]
    #[IsGranted("solve_quiz", subject: "lesson")]
    public function quiz(Lesson $lesson, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken === null || !$this->isCsrfTokenValid('quiz.start', $csrfToken)) {
            $this->addFlash('error', $this->translator->trans('error.lesson.quiz.start', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
        }

        $lessonItemQuiz = $this->em->getRepository(LessonItemQuiz::class)->findOneBy(['lesson' => $lesson]);
        if ($lessonItemQuiz === null) {
            $this->addFlash('error', $this->translator->trans('error.lesson.quiz.missingLessonItemQuiz', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
        }

        $quizData = $this->prepareQuizData($lessonItemQuiz);
        $form = $this->createForm(QuizType::class, $quizData, ['action' => $this->generateUrl('lesson_quiz_finish', ['lesson' => $lesson->getId()])]);
        $lessonsInfo = $this->lessonService->getLessonsInfo($lesson->getCourse(), $user);
        return $this->render('lesson/quiz.html.twig', ['lesson' => $lesson, 'lessonsInfo' => $lessonsInfo, 'form' => $form->createView()]);
    }

    #[Route("/finish-quiz/{lesson}", name: "quiz_finish", methods: ["POST"])]
    #[IsGranted("solve_quiz", subject: "lesson")]
    public function finishQuiz(Lesson $lesson, Request $request, EnrollmentService $enrollmentService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken === null || !$this->isCsrfTokenValid('quiz.finish', $csrfToken)) {
            $this->addFlash('error', $this->translator->trans('error.lesson.quiz.finish', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
        }

        $lessonItemQuiz = $this->em->getRepository(LessonItemQuiz::class)->findOneBy(['lesson' => $lesson]);
        if ($lessonItemQuiz === null) {
            $this->addFlash('error', $this->translator->trans('error.lesson.quiz.missingLessonItemQuiz', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
        }

        $quizData = $this->prepareQuizData($lessonItemQuiz);
        $form = $this->createForm(QuizType::class, $quizData);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $answers = $form->getData()['answers'];
            $quizQuestionRepository = $this->em->getRepository(QuizQuestion::class);
            $quizQuestionAnswerRepository = $this->em->getRepository(QuizQuestionAnswer::class);

            $quizQuestionAnswers = [];
            foreach ($answers as $answer) {
                $quizQuestion = $quizQuestionRepository->find($answer['questionId']);
                if ($quizQuestion !== null) {
                    $quizQuestionAnswer = $quizQuestionAnswerRepository->findOneBy(['user' => $user, 'question' => $quizQuestion]);
                    if ($quizQuestionAnswer === null) $quizQuestionAnswer = new QuizQuestionAnswer();
                    $quizQuestionAnswer->setUser($user)->setQuestion($quizQuestion)->setAnswer($answer['answer']);
                    $quizQuestionAnswers[] = $quizQuestionAnswer;
                    $this->em->persist($quizQuestionAnswer);
                }
            }
            $this->em->flush();

            $sum = 0;
            $questionCount = 0;
            foreach ($quizQuestionAnswers as $quizQuestionAnswer) {
                if ($quizQuestionAnswer->getAnswer() === $quizQuestionAnswer->getQuestion()->getCorrectAnswer()) $sum += 100;
                $questionCount++;
            }
            $percentage = $sum / $questionCount;

            $lessonCompletion = $this->em->getRepository(LessonCompletion::class)->findOneBy(['lesson' => $lesson, 'user' => $this->getUser()]);
            $persistCompletion = false;
            if ($lessonCompletion === null) {
                $lessonCompletion = (new LessonCompletion())->setLesson($lesson)->setUser($this->getUser());
                $persistCompletion = true;
            }
            $lessonCompletion->setCompleted($percentage >= $lessonItemQuiz->getPassingPercentage());
            if ($persistCompletion) $this->em->persist($lessonCompletion);
            $this->em->flush();

            $this->regrade($lesson->getCourse(), $user);
            $this->addFlash('success', $this->translator->trans('success.quiz.finish', ['%quizName%' => $lesson->getName()], 'message'));
        }

        return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
    }

    #[Route("/quiz-results/{lesson}", name: "quiz_results")]
    #[IsGranted("view", subject: "lesson")]
    public function quizResults(Lesson $lesson, LessonRepository $lessonRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($lesson->getType() !== Lesson::TYPE_QUIZ) {
            $this->addFlash('error', $this->translator->trans('error.lesson.quiz.results.type', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
        }

        if (!$this->lessonService->hasCompletionData($lesson, $user)) {
            $this->addFlash('error', $this->translator->trans('error.lesson.quiz.results.completionData', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
        }

        $lessonItemQuiz = $this->em->getRepository(LessonItemQuiz::class)->findOneBy(['lesson' => $lesson]);
        if ($lessonItemQuiz === null) {
            $this->addFlash('error', $this->translator->trans('error.lesson.quiz.results.missingLessonItemQuiz', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
        }

        $quizQuestionAnswerRepository = $this->em->getRepository(QuizQuestionAnswer::class);
        $quizResults = [];
        foreach ($lessonItemQuiz->getQuizQuestions() as $quizQuestion) {
            $quizQuestionAnswer = $quizQuestionAnswerRepository->findOneBy(['question' => $quizQuestion, 'user' => $user]);
            if ($quizQuestionAnswer !== null) {
                $quizResults[] = ['question' => $quizQuestion, 'correct' => $quizQuestionAnswer->getAnswer() === $quizQuestion->getCorrectAnswer(), 'answer' => $quizQuestionAnswer->getAnswer()];
            }
        }

        $lessonCompletion = $this->em->getRepository(LessonCompletion::class)->findOneBy(['lesson' => $lesson, 'user' => $this->getUser()]);
        $lessonsInfo = $this->lessonService->getLessonsInfo($lesson->getCourse(), $user);
        $previousLesson = $lessonRepository->findPreviousLesson($lesson);
        $nextLesson = $lessonRepository->findNextLesson($lesson);
        return $this->render('lesson/quizResults.html.twig', [
            'lesson' => $lesson,
            'quizResults' => $quizResults,
            'lessonCompletion' => $lessonCompletion,
            'lessonsInfo' => $lessonsInfo,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson
        ]);
    }


    #[Route("/reorder", name: "reorder", methods: ["POST"])]
    #[IsGranted('ROLE_MODERATOR')]
    public function reorder(Request $request, LessonRepository $lessonRepository): Response
    {
        $data = json_decode($request->getContent());
        if ($data !== null && isset($data->reorders) && !empty($data->reorders)) {
            foreach ($data->reorders as $reorder) {
                $lesson = $lessonRepository->find($reorder->id);
                $lesson->setPosition(intval($reorder->position));
            }
            $this->em->flush();
            return new JsonResponse(['status' => 'success']);
        }

        return new JsonResponse(['status' => 'fail']);
    }

    #[Route("/toggle-completed/{lesson}", name: "toggle_completed", methods: ["PATCH"])]
    public function toggleCompleted(Lesson $lesson, LessonCompletionRepository $lessonCompletionRepository): JsonResponse
    {
        $response = ['success' => false];

        try {
            $lessonCompletion = $lessonCompletionRepository->findOneBy(['lesson' => $lesson, 'user' => $this->getUser()]);
            if ($lessonCompletion === null) $lessonCompletion = (new LessonCompletion())->setLesson($lesson)->setUser($this->getUser());
            $lessonCompletion->toggleCompleted();
            $lessonCompletionRepository->save($lessonCompletion, true);
            $response['success'] = true;
        } catch (\Exception $ex) {
        }

        if ($response['success']) $response['action'] = $lessonCompletion->isCompleted() ? 'done' : 'undone';
        return new JsonResponse($response);
    }

    private function handleTextLessonType(\Symfony\Component\Form\FormInterface $form, Lesson $lesson, ?LessonItemText $lessonItemText = null): void
    {
        $persist = false;
        if ($lessonItemText === null) {
            $lessonItemText = new LessonItemText();
            $persist = true;
        }
        $lessonItemText->setLesson($lesson);
        $lessonItemText->setText($form->get('text')->getData());
        if ($persist) $this->em->persist($lessonItemText);
        $this->em->flush();
        if ($persist) $this->processTextLessonItemTranslation($form, $lessonItemText);
    }

    private function handleFileLessonType(\Symfony\Component\Form\FormInterface $form, Course $course, Lesson $lesson, ?LessonItemFile $lessonItemFile = null): void
    {
        /** @var UploadedFile $file */
        $file = $form->get('file')->getData();
        $uploadDir = $this->getParameter('dir.lesson_file');
        if ($lessonItemFile !== null) $this->removeFile($uploadDir . '/' . $lessonItemFile->getFilename());
        $filenamePrefix = sprintf('lesson-file-%d-', $course->getId());
        $filename = $this->storeFile($file, $uploadDir, $filenamePrefix);

        $persist = false;
        if ($lessonItemFile === null) {
            $lessonItemFile = new LessonItemFile();
            $persist = true;
        }
        $lessonItemFile->setLesson($lesson);
        $lessonItemFile->setFilename($filename);
        if ($persist) $this->em->persist($lessonItemFile);
        $this->em->flush();
    }

    private function handleVideoLessonType(\Symfony\Component\Form\FormInterface $form, Lesson $lesson, ?LessonItemEmbeddedVideo $lessonItemEmbeddedVideo = null): void
    {
        $persist = false;
        if ($lessonItemEmbeddedVideo === null) {
            $lessonItemEmbeddedVideo = new LessonItemEmbeddedVideo();
            $persist = true;
        }
        $lessonItemEmbeddedVideo->setLesson($lesson);
        $lessonItemEmbeddedVideo->setVideoUrl($form->get('video')->getData());
        if ($persist) $this->em->persist($lessonItemEmbeddedVideo);
        $this->em->flush();
    }

    private function handleQuizLessonType(\Symfony\Component\Form\FormInterface $form, Lesson $lesson, ?LessonItemQuiz $lessonItemQuiz = null): void
    {
        $persist = false;
        if ($lessonItemQuiz === null) {
            $lessonItemQuiz = new LessonItemQuiz();
            $persist = true;
        }
        $lessonItemQuiz->setLesson($lesson);
        $lessonItemQuiz->setPassingPercentage(intval($form->get('passingPercentage')->getData()));
        if ($persist) $this->em->persist($lessonItemQuiz);
        $this->em->flush();

        foreach ($lesson->getCourse()->getEnrollments() as $enrollment) {
            $this->regrade($enrollment->getCourse(), $enrollment->getUser());
        }
    }

    private function processLessonTranslation(Lesson $lesson, \Symfony\Component\Form\FormInterface $form): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $nameAlt = $form->get('nameAlt')->getData();
        if ($nameAlt !== null && trim($nameAlt) !== '') {
            $translationRepository->translate($lesson, 'name', $localeAlt, trim($nameAlt));
            $translated = true;
        }

        $descriptionAlt = $form->get('descriptionAlt')->getData();
        if ($descriptionAlt !== null && trim($descriptionAlt) !== '') {
            $translationRepository->translate($lesson, 'description', $localeAlt, trim($descriptionAlt));
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }

    private function processTextLessonItemTranslation(\Symfony\Component\Form\FormInterface $form, ?LessonItemText $lessonItemText): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $textAlt = $form->get('textAlt')->getData();
        if ($textAlt !== null && trim($textAlt) !== '') {
            $translationRepository->translate($lessonItemText, 'text', $localeAlt, $textAlt);
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }

    private function processQuizQuestionTranslation(\Symfony\Component\Form\FormInterface $form, QuizQuestion $quizQuestion)
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $textAlt = $form->get('textAlt')->getData();
        if ($textAlt !== null && trim($textAlt) !== '') {
            $translationRepository->translate($quizQuestion, 'text', $localeAlt, $textAlt);
            $translated = true;
        }

        $explanationAlt = $form->get('explanationAlt')->getData();
        if ($explanationAlt !== null && trim($explanationAlt) !== '') {
            $translationRepository->translate($quizQuestion, 'explanation', $localeAlt, $explanationAlt);
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }

    private function prepareQuizData(LessonItemQuiz $lessonItemQuiz): array
    {
        $quizData = ['lesson' => $lessonItemQuiz->getLesson(), 'answers' => []];
        foreach ($lessonItemQuiz->getQuizQuestions() as $quizQuestion) {
            $quizData['answers'][] = [
                'questionId' => $quizQuestion->getId(),
                'text' => $quizQuestion->getText(),
                'answer' => false
            ];
        }
        return $quizData;
    }

    private function regrade(Course $course, User $user): void
    {
        if ($this->enrollmentService->checkCoursePassingCondition($course, $user)) {
            $this->enrollmentService->markAsPassed($course, $user);
        } else {
            $this->enrollmentService->markAsNotPassed($course, $user);
        }
    }
}
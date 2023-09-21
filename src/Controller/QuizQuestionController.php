<?php

namespace App\Controller;

use App\Entity\QuizQuestion;
use App\Entity\QuizQuestionAnswer;
use App\Entity\QuizQuestionChoice;
use App\Entity\User;
use App\Form\QuizQuestionChoiceType;
use App\Form\QuizQuestionType;
use App\Service\LessonService;
use App\Service\NavigationService;
use App\Service\QuizQuestionService;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale}/quiz-question', name: 'quiz_question_', requirements: ["_locale" => "%locale.supported%"])]
#[IsGranted("ROLE_MODERATOR")]
class QuizQuestionController extends BaseController
{
    #[Route("/edit/{quizQuestion}", name: "edit")]
    public function edit(QuizQuestion $quizQuestion, Request $request, LessonService $lessonService, NavigationService $navigationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(QuizQuestionType::class, $quizQuestion, ['edit_mode' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.quizQuestion.edit', [], 'message'));
            return $this->redirectToRoute('lesson_show', ['lesson' => $quizQuestion->getQuiz()->getLesson()->getId()]);
        } else {
            $this->showFormErrorsAsFlashes($form);
            $this->em->refresh($quizQuestion);
        }

        return $this->render('lesson/quiz/edit.html.twig', [
            'quizQuestion' => $quizQuestion,
            'lesson' => $quizQuestion->getQuiz()->getLesson(),
            'lessonsInfo' => $lessonService->getLessonsInfo($quizQuestion->getQuiz()->getLesson()->getCourse(), $user),
            'form' => $form->createView(),
            'navigation' => $navigationService->forCourse($quizQuestion->getQuiz()->getLesson()->getCourse(), NavigationService::COURSE_LESSONS)
        ]);
    }

    #[Route("/delete/{quizQuestion}", name: "delete", methods: ["POST"])]
    public function delete(QuizQuestion $quizQuestion, Request $request): Response
    {
        $lesson = $quizQuestion->getQuiz()->getLesson();
        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('quiz.question.delete', $csrfToken)) {
            foreach ($this->em->getRepository(QuizQuestionAnswer::class)->findBy(['question' => $quizQuestion]) as $quizQuestionAnswer) {
                $this->em->remove($quizQuestionAnswer);
            }
            $this->em->remove($quizQuestion);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.quizQuestion.delete', ['%lesson%' => $lesson->getName()], 'message'));
        }

        return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
    }

    #[Route("/add-choice/{quizQuestion}", name: "add_choice")]
    public function addChoice(QuizQuestion $quizQuestion,
                              Request $request,
                              LessonService $lessonService,
                              NavigationService $navigationService,
                              QuizQuestionService $quizQuestionService
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $qqc = (new QuizQuestionChoice())->setQuizQuestion($quizQuestion)->setLocale($this->getParameter('locale.default'));
        $form = $this->createForm(QuizQuestionChoiceType::class, $qqc, ['include_translatable_fields' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($qqc);
            $this->em->flush();
            $changed = $quizQuestionService->resolveOnlyOneChoiceCorrect($qqc);
            $this->processQuizQuestionChoiceTextTranslation($qqc, $form);
            if ($changed) $this->addFlash('warning', $this->translator->trans('warning.quizQuestionChoice.correctAnswerChanged', domain: 'message'));
            $this->addFlash('success', $this->translator->trans('success.quizQuestionChoice.new', domain: 'message'));
            return $this->redirectToRoute('quiz_question_edit', ['quizQuestion' => $quizQuestion->getId()]);
        } else {
            $this->showFormErrorsAsFlashes($form);
        }

        return $this->render('lesson/quiz/choice/new.html.twig', [
            'quizQuestion' => $quizQuestion,
            'lesson' => $quizQuestion->getQuiz()->getLesson(),
            'lessonsInfo' => $lessonService->getLessonsInfo($quizQuestion->getQuiz()->getLesson()->getCourse(), $user),
            'form' => $form->createView(),
            'navigation' => $navigationService->forCourse($quizQuestion->getQuiz()->getLesson()->getCourse(), NavigationService::COURSE_LESSONS)
        ]);
    }

    private function processQuizQuestionChoiceTextTranslation(QuizQuestionChoice $qqc, \Symfony\Component\Form\FormInterface $form): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $textAlt = $form->get('textAlt')->getData();
        if (null !== $textAlt && '' !== trim($textAlt)) {
            $translationRepository->translate($qqc, 'text', $localeAlt, $textAlt);
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }
}
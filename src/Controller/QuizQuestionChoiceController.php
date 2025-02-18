<?php

namespace App\Controller;

use App\Entity\QuizQuestionChoice;
use App\Entity\User;
use App\Form\QuizQuestionChoiceType;
use App\Service\LessonService;
use App\Service\NavigationService;
use App\Service\QuizQuestionService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/quiz-question-choice", name: "quiz_question_choice_", requirements: ["_locale" => "%locale.supported%"])]
#[IsGranted("ROLE_MODERATOR")]
class QuizQuestionChoiceController extends BaseController
{
    #[Route("/edit/{qqc}", name: "edit")]
    public function edit(QuizQuestionChoice $qqc,
                         Request $request,
                         LessonService $lessonService,
                         NavigationService $navigationService,
                         QuizQuestionService $quizQuestionService): Response
    {
        $form = $this->createForm(QuizQuestionChoiceType::class, $qqc);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $changed = $quizQuestionService->resolveOnlyOneChoiceCorrect($qqc);
            if ($changed) $this->addFlash('warning', $this->translator->trans('warning.quizQuestionChoice.correctAnswerChanged', domain: 'message'));
            $this->addFlash('success', $this->translator->trans('success.quizQuestionChoice.edit', domain: 'message'));
        }

        /** @var User $user */
        $user = $this->getUser();
        $quizQuestion = $qqc->getQuizQuestion();
        return $this->render('lesson/quiz/choice/edit.html.twig', [
            'quizQuestion' => $quizQuestion,
            'lesson' => $quizQuestion->getQuiz()->getLesson(),
            'lessonsInfo' => $lessonService->getLessonsInfo($quizQuestion->getQuiz()->getLesson()->getCourse(), $user),
            'form' => $form->createView(),
            'navigation' => $navigationService->forCourse($quizQuestion->getQuiz()->getLesson()->getCourse(), NavigationService::COURSE_LESSONS)
        ]);
    }

    #[Route("/delete/{qqc}", name: "delete", methods: ["POST"])]
    public function delete(QuizQuestionChoice $qqc, Request $request): Response
    {
        $quizQuestionId = $qqc->getQuizQuestion()->getId();
        $csrfToken = $request->get('_csrf_token');
        if (null !== $csrfToken && $this->isCsrfTokenValid('qqc.delete', $csrfToken)) {
            $this->em->remove($qqc);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.quizQuestionChoice.delete', domain: 'message'));
        }
        return $this->redirectToRoute('quiz_question_edit', ['quizQuestion' => $quizQuestionId]);
    }
}
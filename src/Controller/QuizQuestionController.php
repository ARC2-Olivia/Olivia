<?php

namespace App\Controller;

use App\Entity\QuizQuestion;
use App\Entity\User;
use App\Form\QuizQuestionType;
use App\Service\LessonService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale}/quiz-question', name: 'quiz_question_')]
class QuizQuestionController extends BaseController
{
    #[Route("/edit/{quizQuestion}", name: "edit")]
    #[IsGranted("ROLE_MODERATOR")]
    public function edit(QuizQuestion $quizQuestion, Request $request, LessonService $lessonService): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(QuizQuestionType::class, $quizQuestion);

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
            'form' => $form->createView()
        ]);
    }

    #[Route("/delete/{quizQuestion}", name: "delete", methods: ["POST"])]
    #[IsGranted("ROLE_MODERATOR")]
    public function delete(QuizQuestion $quizQuestion, Request $request): Response
    {
        $lesson = $quizQuestion->getQuiz()->getLesson();
        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('quiz.question.delete', $csrfToken)) {
            $this->em->remove($quizQuestion);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.quizQuestion.delete', ['%lesson%' => $lesson->getName()], 'message'));
        }

        return $this->redirectToRoute('lesson_show', ['lesson' => $lesson->getId()]);
    }
}
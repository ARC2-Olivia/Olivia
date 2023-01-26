<?php

namespace App\Controller;

use App\Entity\EvaluationQuestion;
use App\Entity\EvaluationQuestionAnswer;
use App\Form\EvaluationQuestionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/evaluation-question", name: "evaluation_question_")]
class EvaluationQuestionController extends BaseController
{
    #[Route("/edit/{evaluationQuestion}", name: "edit")]
    #[IsGranted("ROLE_MODERATOR")]
    public function edit(EvaluationQuestion $evaluationQuestion, Request $request): Response
    {
        $form = $this->createForm(EvaluationQuestionType::class, $evaluationQuestion);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.evaluationQuestion.edit', [], 'message'));
            return $this->redirectToRoute('evaluation_evaluate', ['evaluation' => $evaluationQuestion->getEvaluation()->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_question/edit.html.twig', [
            'evaluationQuestion' => $evaluationQuestion,
            'evaluation' => $evaluationQuestion->getEvaluation(),
            'form' => $form->createView(),
            'activeCard' => 'editQuestion'
        ]);
    }

    #[Route("/delete/{evaluationQuestion}", name: "delete", methods: ["POST"])]
    #[IsGranted("ROLE_MODERATOR")]
    public function delete(EvaluationQuestion $evaluationQuestion, Request $request): Response
    {
        $evaluation = $evaluationQuestion->getEvaluation();
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluationQuestion.delete', $csrfToken)) {
            $this->em->remove($evaluationQuestion);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.evaluationQuestion.delete', ['%evaluation%' => $evaluation->getName()], 'message'));
        }

        return $this->redirectToRoute('evaluation_evaluate', ['evaluation' => $evaluation->getId()]);
    }

    #[Route("/add-answers/{evaluationQuestion}", name: "add_answers", methods: ["POST"])]
    #[IsGranted("ROLE_MODERATOR")]
    public function addAnswers(EvaluationQuestion $evaluationQuestion, Request $request): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        $answers = json_decode($request->request->get('answers'), true);

        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluationQuestion.addAnswers', $csrfToken)) {
            if (count($answers) > 0) {
                $this->clearExistingEvaluationQuestionAnswers($evaluationQuestion);
                $this->createNewEvaluationQuestionAnswers($answers, $evaluationQuestion);
                $this->em->flush();
                $this->addFlash('success', $this->translator->trans('success.evaluationQuestionAnswer.add', [], 'message'));
            } else {
                $this->addFlash('error', $this->translator->trans('error.evaluationQuestionAnswer.empty', [], 'message'));
            }
        } else {
            $this->addFlash('error', $this->translator->trans('error.evaluationQuestionAnswer.csrf', [], 'message'));
        }

        return $this->redirectToRoute('evaluation_question_edit', ['evaluationQuestion' => $evaluationQuestion->getId()]);
    }

    private function clearExistingEvaluationQuestionAnswers(EvaluationQuestion $evaluationQuestion): void
    {
        foreach ($evaluationQuestion->getEvaluationQuestionAnswers() as $eqa) {
            $this->em->remove($eqa);
        }
    }

    private function createNewEvaluationQuestionAnswers(mixed $answers, EvaluationQuestion $evaluationQuestion): void
    {
        foreach ($answers as $answer) {
            $eqa = (new EvaluationQuestionAnswer())
                ->setEvaluationQuestion($evaluationQuestion)
                ->setAnswerText($answer['answerText'])
                ->setAnswerValue($answer['answerValue']);
            $this->em->persist($eqa);
        }
    }
}
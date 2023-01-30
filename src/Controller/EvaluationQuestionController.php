<?php

namespace App\Controller;

use App\Entity\EvaluationQuestion;
use App\Entity\EvaluationQuestionAnswer;
use App\Form\EvaluationQuestionAnswerWeightedType;
use App\Form\EvaluationQuestionType;
use Gedmo\Translatable\Entity\Translation;
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
        $form = $this->createForm(EvaluationQuestionType::class, $evaluationQuestion, ['edit_mode' => true]);
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
            foreach ($evaluationQuestion->getEvaluationQuestionAnswers() as $evaluationQuestionAnswer) {
                $this->em->remove($evaluationQuestionAnswer);
            }
            $this->em->remove($evaluationQuestion);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.evaluationQuestion.delete', ['%evaluation%' => $evaluation->getName()], 'message'));
        }

        return $this->redirectToRoute('evaluation_evaluate', ['evaluation' => $evaluation->getId()]);
    }

    #[Route("/add-weighted-answer/{evaluationQuestion}", name: "add_weighted_answer")]
    #[IsGranted("ROLE_MODERATOR")]
    public function addWeightedAnswer(EvaluationQuestion $evaluationQuestion, Request $request): Response
    {
        $evaluationQuestionAnswer = (new EvaluationQuestionAnswer())->setEvaluationQuestion($evaluationQuestion)->setLocale($this->getParameter('locale.default'));
        $form = $this->createForm(EvaluationQuestionAnswerWeightedType::class, $evaluationQuestionAnswer, ['include_translatable_fields' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($evaluationQuestionAnswer);
            $this->em->flush();
            $this->processEvaluationQuestionAnswerTranslation($evaluationQuestionAnswer, $form);
            $this->addFlash('success', $this->translator->trans('success.evaluationQuestionAnswer.new', [], 'message'));
            return $this->redirectToRoute('evaluation_question_edit', ['evaluationQuestion' => $evaluationQuestion->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $error->getMessage());
            }
        }

        return $this->render('evaluation/evaluation_question/evaluation_question_answer/new.html.twig', [
            'evaluation' => $evaluationQuestion->getEvaluation(),
            'evaluationQuestion' => $evaluationQuestion,
            'form' => $form->createView(),
            'activeCard' => 'newAnswer'
        ]);
    }

    private function processEvaluationQuestionAnswerTranslation(EvaluationQuestionAnswer $evaluationQuestionAnswer, \Symfony\Component\Form\FormInterface $form)
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $translated = false;

        $answerTextAlt = $form->get('answerTextAlt')->getData();
        if ($answerTextAlt !== null && trim($answerTextAlt) !== '') {
            $translationRepository->translate($evaluationQuestionAnswer, 'answerText', $localeAlt, trim($answerTextAlt));
            $translated = true;
        }

        if ($translated) $this->em->flush();
    }
}
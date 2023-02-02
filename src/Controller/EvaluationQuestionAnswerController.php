<?php

namespace App\Controller;

use App\Entity\EvaluationQuestionAnswer;
use App\Form\EvaluationQuestionAnswerWeightedType;
use App\Service\NavigationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/evaluation-question-answer", name: "evaluation_question_answer_")]
class EvaluationQuestionAnswerController extends BaseController
{
    #[Route("/edit-weighted/{evaluationQuestionAnswer}", name: "edit")]
    #[IsGranted('ROLE_MODERATOR')]
    public function editWeighted(EvaluationQuestionAnswer $evaluationQuestionAnswer, Request $request, NavigationService $navigationService): Response
    {
        $form = $this->createForm(EvaluationQuestionAnswerWeightedType::class, $evaluationQuestionAnswer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.evaluationQuestionAnswer.edit', [], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/evaluation_question/evaluation_question_answer/edit.html.twig', [
            'evaluation' => $evaluationQuestionAnswer->getEvaluationQuestion()->getEvaluation(),
            'evaluationQuestionAnswer' => $evaluationQuestionAnswer,
            'form' => $form->createView(),
            'navigation' => $navigationService->forEvaluation($evaluationQuestionAnswer->getEvaluationQuestion()->getEvaluation(), NavigationService::EVALUATION_EXTRA_EDIT_ANSWER)
        ]);
    }

    #[Route("/delete/{evaluationQuestionAnswer}", name: "delete", methods: ["POST"])]
    public function delete(EvaluationQuestionAnswer $evaluationQuestionAnswer, Request $request): Response
    {
        $evaluationQuestion = $evaluationQuestionAnswer->getEvaluationQuestion();
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluationQuestionAnswer.delete', $csrfToken)) {
            $this->em->remove($evaluationQuestionAnswer);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.evaluationQuestionAnswer.delete', [], 'message'));
        }

        return $this->redirectToRoute('evaluation_question_edit', ['evaluationQuestion' => $evaluationQuestion->getId()]);
    }
}
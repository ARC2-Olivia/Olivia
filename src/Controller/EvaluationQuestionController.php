<?php

namespace App\Controller;

use App\Entity\EvaluationQuestion;
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

        return $this->render('evaluation/evaluation_question/form.html.twig', ['evaluation' => $evaluationQuestion->getEvaluation(), 'form' => $form->createView(), 'activeCard' => 'editQuestion']);
    }
}
<?php

namespace App\Controller;

use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Form\PracticalSubmoduleQuestionAnswerWeightedType;
use App\Service\NavigationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/practical-submodule-question-answer", name: "evaluation_question_answer_")]
class EvaluationQuestionAnswerController extends BaseController
{
    #[Route("/edit-weighted/{practicalSubmoduleQuestionAnswer}", name: "edit")]
    #[IsGranted('ROLE_MODERATOR')]
    public function editWeighted(PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer, Request $request, NavigationService $navigationService): Response
    {
        $form = $this->createForm(PracticalSubmoduleQuestionAnswerWeightedType::class, $practicalSubmoduleQuestionAnswer);
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
            'evaluation' => $practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion()->getPracticalSubmodule(),
            'evaluationQuestionAnswer' => $practicalSubmoduleQuestionAnswer,
            'form' => $form->createView(),
            'navigation' => $navigationService->forPracticalSubmodule($practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion()->getPracticalSubmodule(), NavigationService::EVALUATION_EXTRA_EDIT_ANSWER)
        ]);
    }

    #[Route("/delete/{practicalSubmoduleQuestionAnswer}", name: "delete", methods: ["POST"])]
    public function delete(PracticalSubmoduleQuestionAnswer $practicalSubmoduleQuestionAnswer, Request $request): Response
    {
        $practicalSubmoduleQuestion = $practicalSubmoduleQuestionAnswer->getPracticalSubmoduleQuestion();
        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluationQuestionAnswer.delete', $csrfToken)) {
            $this->em->remove($practicalSubmoduleQuestionAnswer);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.evaluationQuestionAnswer.delete', [], 'message'));
        }

        return $this->redirectToRoute('evaluation_question_edit', ['practicalSubmoduleQuestion' => $practicalSubmoduleQuestion->getId()]);
    }
}
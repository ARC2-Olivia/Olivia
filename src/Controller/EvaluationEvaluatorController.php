<?php

namespace App\Controller;

use App\Entity\EvaluationEvaluator;
use App\Form\EvaluationEvaluatorType;
use App\Service\EvaluationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/evaluation-evaluator', name: 'evaluation_evaluator_')]
class EvaluationEvaluatorController extends BaseController
{
    #[Route('/delete/{evaluationEvaluator}', name: 'delete')]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(EvaluationEvaluator $evaluationEvaluator, Request $request): Response
    {
        $evaluation = $evaluationEvaluator->getEvaluation();

        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('evaluationEvaluator.delete', $csrfToken)) {
            $this->em->remove($evaluationEvaluator);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.evaluationEvaluator.delete', [], 'message'));
        }

        return $this->redirectToRoute('evaluation_evaluate', ['evaluation' => $evaluation->getId()]);
    }

    #[Route('/edit/{evaluationEvaluator}', name: 'edit')]
    #[IsGranted('ROLE_MODERATOR')]
    public function edit(EvaluationEvaluator $evaluationEvaluator, Request $request, EvaluationService $evaluationService): Response
    {
        $evaluationEvaluatorImpl = $evaluationService->getEvaluatorImplementation($evaluationEvaluator);
        $baseForm = $this->createForm(EvaluationEvaluatorType::class, $evaluationEvaluator, ['edit_mode' => true]);
        $implForm = $this->createForm($evaluationService->getEvaluatorImplementationFormClass($evaluationEvaluator), $evaluationEvaluatorImpl);
        $updated = false;

        $baseForm->handleRequest($request);
        if ($baseForm->isSubmitted() && $baseForm->isValid()) {
            $this->em->flush();
            $updated = true;
        } else {
            foreach ($baseForm->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        $implForm->handleRequest($request);
        if ($implForm->isSubmitted() && $implForm->isValid()) {
            if ($evaluationEvaluatorImpl->getId() === null) $this->em->persist($evaluationEvaluatorImpl);
            $this->em->flush();
            $updated = true;
        } else {
            foreach ($implForm->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        if ($updated === true) $this->addFlash('success', $this->translator->trans('success.evaluationEvaluator.edit', [], 'message'));
        return $this->render('evaluation/evaluation_evaluator/edit.html.twig', [
            'evaluation' => $evaluationEvaluator->getEvaluation(),
            'evaluationEvaluator' => $evaluationEvaluator,
            'baseForm' => $baseForm->createView(),
            'implForm' => $implForm->createView(),
            'activeCard' => 'editEvaluator'
        ]);
    }
}
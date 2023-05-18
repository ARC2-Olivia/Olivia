<?php

namespace App\Controller;

use App\Entity\PracticalSubmoduleProcessor;
use App\Form\PracticalSubmoduleProcessorType;
use App\Repository\PracticalSubmoduleProcessorRepository;
use App\Service\PracticalSubmoduleService;
use App\Service\NavigationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale}/practical-submodule-processor', name: 'practical_submodule_processor_')]
class PracticalSubmoduleProcessorController extends BaseController
{
    #[Route('/delete/{practicalSubmoduleProcessor}', name: 'delete')]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(PracticalSubmoduleProcessor $practicalSubmoduleProcessor, Request $request): Response
    {
        $evaluation = $practicalSubmoduleProcessor->getPracticalSubmodule();

        $csrfToken = $request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('practicalSubmoduleProcessor.delete', $csrfToken)) {
            $this->em->remove($practicalSubmoduleProcessor);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.practicalSubmoduleProcessor.delete', [], 'message'));
        }

        return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $evaluation->getId()]);
    }

    #[Route('/edit/{practicalSubmoduleProcessor}', name: 'edit')]
    #[IsGranted('ROLE_MODERATOR')]
    public function edit(PracticalSubmoduleProcessor $practicalSubmoduleProcessor,
                         PracticalSubmoduleService $practicalSubmoduleService,
                         NavigationService $navigationService,
                         Request $request,
    ): Response
    {
        $processorImpl = $practicalSubmoduleService->getProcessorImplementation($practicalSubmoduleProcessor);
        $baseForm = $this->createForm(PracticalSubmoduleProcessorType::class, $practicalSubmoduleProcessor, ['edit_mode' => true]);
        $implForm = $this->createForm($practicalSubmoduleService->getProcessorImplementationFormClass($practicalSubmoduleProcessor), $processorImpl);
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
            if ($processorImpl->getId() === null) $this->em->persist($processorImpl);
            $this->em->flush();
            $updated = true;
        } else {
            foreach ($implForm->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        if ($updated === true) $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleProcessor.edit', [], 'message'));
        return $this->render('evaluation/evaluation_evaluator/edit.html.twig', [
            'evaluation' => $practicalSubmoduleProcessor->getPracticalSubmodule(),
            'evaluationEvaluator' => $practicalSubmoduleProcessor,
            'baseForm' => $baseForm->createView(),
            'implForm' => $implForm->createView(),
            'navigation' => $navigationService->forPracticalSubmodule($practicalSubmoduleProcessor->getPracticalSubmodule(), NavigationService::EVALUATION_EXTRA_EDIT_EVALUATOR)
        ]);
    }

    #[Route("/reorder", name: "reorder", methods: ["POST"])]
    #[IsGranted("ROLE_MODERATOR")]
    public function reorder(Request $request, PracticalSubmoduleProcessorRepository $practicalSubmoduleProcessorRepository): Response
    {
        $data = json_decode($request->getContent());
        if ($data !== null && isset($data->reorders) && !empty($data->reorders)) {
            foreach ($data->reorders as $reorder) {
                $practicalSubmoduleProcessor = $practicalSubmoduleProcessorRepository->find($reorder->id);
                $practicalSubmoduleProcessor->setPosition($reorder->position);
            }
            $this->em->flush();
            return new JsonResponse(['status' => 'success']);
        }

        return new JsonResponse(['status' => 'fail']);
    }
}
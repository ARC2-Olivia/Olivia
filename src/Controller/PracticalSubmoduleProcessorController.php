<?php

namespace App\Controller;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorMaxValue;
use App\Entity\PracticalSubmoduleProcessorProductAggregate;
use App\Entity\PracticalSubmoduleProcessorSumAggregate;
use App\Entity\PracticalSubmoduleQuestion;
use App\Form\PracticalSubmoduleProcessorType;
use App\Repository\PracticalSubmoduleProcessorRepository;
use App\Service\PracticalSubmoduleService;
use App\Service\NavigationService;
use App\Service\SanitizerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/{_locale}/practical-submodule-processor', name: 'practical_submodule_processor_', requirements: ["_locale" => "%locale.supported%"])]
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
                         SanitizerService $sanitizerService,
                         NavigationService $navigationService,
                         Request $request,
    ): Response
    {
        $processorImpl = $practicalSubmoduleService->getProcessorImplementation($practicalSubmoduleProcessor);
        $updated = false;
        $oldIncluded = $practicalSubmoduleProcessor->isIncluded();
        $isProcessorProcessingProcessor = in_array($practicalSubmoduleProcessor->getType(), PracticalSubmoduleProcessor::getProcessorProcessingProcessorTypes());

        if (PracticalSubmoduleProcessor::TYPE_HTML !== $practicalSubmoduleProcessor->getType()) {
            $processorImpl->setResultText($processorImpl->getResultText());
        }

        $baseForm = $this->createForm(PracticalSubmoduleProcessorType::class, $practicalSubmoduleProcessor, [
            'edit_mode' => true,
            'include_export_tag' => in_array($practicalSubmoduleProcessor->getPracticalSubmodule()->getExportType(), PracticalSubmodule::getTaggableExportTypes())
        ]);
        $implForm = $this->createForm($practicalSubmoduleService->getProcessorImplementationFormClass($practicalSubmoduleProcessor), $processorImpl);

        $baseForm->handleRequest($request);
        if ($baseForm->isSubmitted() && $baseForm->isValid()) {
            if ($oldIncluded !== $practicalSubmoduleProcessor->isIncluded()
                && true === $isProcessorProcessingProcessor
                && $practicalSubmoduleProcessor->getPracticalSubmodule()->isSimpleModeOfOperation()
            ) {
                /** @var PracticalSubmoduleProcessorSumAggregate|PracticalSubmoduleProcessorProductAggregate $processorImpl */
                $processorImpl->getPracticalSubmoduleQuestions()->clear();
            }
            $this->em->flush();
            $updated = true;
        } else {
            foreach ($baseForm->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        $implForm->handleRequest($request);
        if ($implForm->isSubmitted() && $implForm->isValid()) {
            $processorImpl->setResultText($sanitizerService->sanitizeHtml($processorImpl->getResultText()));

            if ($processorImpl->getId() === null) {
                $this->em->persist($processorImpl);
            }

            if ($implForm->has('resultFiles')) {
                $files = $implForm->get('resultFiles')->getData();
                $practicalSubmoduleProcessor->clearResultFiles();
                foreach ($files as $file) {
                    $practicalSubmoduleProcessor->addResultFile($file);
                }
            }

            if (true === $isProcessorProcessingProcessor
                && $practicalSubmoduleProcessor::TYPE_RESULT_COMBINER !== $practicalSubmoduleProcessor->getType()
                && $practicalSubmoduleProcessor::TYPE_MAX_VALUE !== $practicalSubmoduleProcessor->getType()
            ) {
                /** @var PracticalSubmoduleProcessorSumAggregate|PracticalSubmoduleProcessorProductAggregate $processorImpl */
                $processorImpl->getPracticalSubmoduleQuestions()->clear();
                $simpleMode = true === $practicalSubmoduleProcessor->isIncluded() && PracticalSubmodule::MODE_OF_OPERATION_SIMPLE === $practicalSubmoduleProcessor->getPracticalSubmodule()->getModeOfOperation();
                $questionRepository = $this->em->getRepository(PracticalSubmoduleQuestion::class);
                if (true === $simpleMode) {
                    $question = $implForm->get('question')->getData();
                    if (null !== $question) $processorImpl->getPracticalSubmoduleQuestions()->add($question);
                } else {
                    $questionIds = $implForm->get('questions')->getViewData();
                    if (null !== $questionIds) {
                        foreach ($questionIds as $questionId) $processorImpl->getPracticalSubmoduleQuestions()->add($questionRepository->find($questionId));
                    }
                }
            }

            $this->em->flush();
            $updated = true;
        } else {
            foreach ($implForm->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        if ($updated === true) {
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleProcessor.edit', [], 'message'));
            return $this->redirectToRoute('practical_submodule_processor_edit', ['practicalSubmoduleProcessor' => $practicalSubmoduleProcessor->getId()]);
        }
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
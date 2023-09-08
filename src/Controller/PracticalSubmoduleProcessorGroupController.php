<?php

namespace App\Controller;

use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorGroup;
use App\Form\PracticalSubmoduleProcessorGroupType;
use App\Service\NavigationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/practical-submodule-processor-group", name: 'practical_submodule_processor_group_', requirements: ["_locale" => "%locale.supported%"])]
class PracticalSubmoduleProcessorGroupController extends BaseController
{
    #[Route("/edit/{practicalSubmoduleProcessorGroup}", name: "edit")]
    #[IsGranted('ROLE_MODERATOR')]
    public function edit(PracticalSubmoduleProcessorGroup $practicalSubmoduleProcessorGroup, NavigationService $navigationService, Request $request): Response
    {
        $practicalSubmodule = $practicalSubmoduleProcessorGroup->getPracticalSubmodule();
        $form = $this->createForm(PracticalSubmoduleProcessorGroupType::class, $practicalSubmoduleProcessorGroup, ['edit_mode' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $currentlySelectedProcessors = $this->em->getRepository(PracticalSubmoduleProcessor::class)->findBy(['practicalSubmoduleProcessorGroup' => $practicalSubmoduleProcessorGroup]);
            foreach ($currentlySelectedProcessors as $processor) {
                $processor->setPracticalSubmoduleProcessorGroup(null);
            }

            /** @var PracticalSubmoduleProcessor $newlySelectedProcessors */
            $newlySelectedProcessors = $form->get('processors')->getData();
            if (null !== $newlySelectedProcessors) {
                foreach ($newlySelectedProcessors as $processor) {
                    $processor->setPracticalSubmoduleProcessorGroup($practicalSubmoduleProcessorGroup);
                }
            }

            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.practicalSubmoduleProcessorGroup.edit', [], 'message'));
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('evaluation/processorGroup/edit.html.twig', [
            'form' => $form->createView(),
            'evaluation' => $practicalSubmodule,
            'navigation' => $navigationService->forPracticalSubmodule($practicalSubmodule, NavigationService::EVALUATION_EXTRA_EDIT_PROCESSOR_GROUP)
        ]);
    }
}
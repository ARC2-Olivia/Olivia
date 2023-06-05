<?php

namespace App\Controller;

use App\Entity\PracticalSubmodulePage;
use App\Entity\PracticalSubmoduleQuestion;
use App\Repository\PracticalSubmodulePageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/{_locale}/practical-submodule-page", name: 'practical_submodule_page_', requirements: ["_locale" => "%locale.supported%"])]
class PracticalSubmodulePageController extends BaseController
{
    #[Route("/delete/{practicalSubmodulePage}", name: "delete")]
    #[IsGranted("ROLE_MODERATOR")]
    public function delete(PracticalSubmodulePage $practicalSubmodulePage, Request $request): Response
    {
        $practicalSubmodule = $practicalSubmodulePage->getPracticalSubmodule();

        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('practicalSubmodulePage.delete', $csrfToken)) {
            $practicalSubmodulePage->removeItselfFromPracticalSubmoduleQuestions();
            $this->em->remove($practicalSubmodulePage);
            $this->em->flush();
            $this->addFlash('warning', $this->translator->trans('warning.practicalSubmodulePage.delete', [], 'message'));
        }

        return $this->redirectToRoute('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmodule->getId()]);
    }

    #[Route("/reorder", name: "reorder", methods: ["POST"])]
    #[IsGranted("ROLE_MODERATOR")]
    public function reorder(Request $request, PracticalSubmodulePageRepository $practicalSubmodulePageRepository): Response
    {
        $data = json_decode($request->getContent());
        if ($data !== null && !empty($data->reorders)) {
            foreach ($data->reorders as $reorder) {
                $practicalSubmodulePage = $practicalSubmodulePageRepository->find($reorder->id);
                $practicalSubmodulePage->setPosition($reorder->position);
            }
            $this->em->flush();
            return new JsonResponse(['status' => 'success']);
        }

        return new JsonResponse(['status' => 'fail']);
    }
}
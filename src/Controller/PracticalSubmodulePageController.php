<?php

namespace App\Controller;

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
<?php

namespace App\Controller;

use App\Controller\BaseController;
use App\Repository\FileRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/file', name: 'file_')]
class FileController extends BaseController
{
    #[Route('/file/reorder-webinars', name: 'reorder_webinars', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function reorderWebinars(Request $request, FileRepository $fileRepository): JsonResponse
    {
        $data = json_decode($request->getContent());
        $canReorder = $data !== null && !empty($data->reorders);

        if (!$canReorder) {
            return new JsonResponse(['status' => 'fail']);
        }

        foreach ($data->reorders as $reorder) {
            $file = $fileRepository->find($reorder->id);
            $file->setWebinarOrder(intval($reorder->position));
        }
        $this->em->flush();
        return new JsonResponse(['status' => 'success']);
    }

    #[Route('/file/reorder-presentations', name: 'reorder_presentations', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function reorderPresentations(Request $request, FileRepository $fileRepository): JsonResponse
    {
        $data = json_decode($request->getContent());
        $canReorder = $data !== null && !empty($data->reorders);

        if (!$canReorder) {
            return new JsonResponse(['status' => 'fail']);
        }

        foreach ($data->reorders as $reorder) {
            $file = $fileRepository->find($reorder->id);
            $file->setPresentationOrder(intval($reorder->position));
        }
        $this->em->flush();
        return new JsonResponse(['status' => 'success']);
    }
}
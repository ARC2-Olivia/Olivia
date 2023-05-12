<?php

namespace App\Controller;

use App\Entity\DataRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/data-request", name: "data_request_")]
class DataRequestController extends AbstractController
{
    private EntityManagerInterface $em;
    private TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    #[Route("/access", name: "access")]
    public function requestDataAccess(Request $request): Response
    {
        $csrfToken = $request->request->get('_csrf_token');

        if ($csrfToken !== null && $this->isCsrfTokenValid('data.request.access', $csrfToken)) {
            $dataRequest = DataRequest::createAccessRequest($this->getUser());
            $this->em->persist($dataRequest);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.dataRequest.access', [], 'message'));
        } else {
            $this->addFlash('error', $this->translator->trans('error.dataRequest.access', [], 'message'));
        }

        return $this->redirectToRoute('profile');
    }

    #[Route("/delete", name: "delete")]
    public function requestDataDeletion(Request $request): Response
    {
        $csrfToken = $request->request->get('_csrf_token');

        if ($csrfToken !== null && $this->isCsrfTokenValid('data.request.delete', $csrfToken)) {
            $dataRequest = DataRequest::createDeletionRequest($this->getUser());
            $this->em->persist($dataRequest);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.dataRequest.delete', [], 'message'));
        } else {
            $this->addFlash('error', $this->translator->trans('error.dataRequest.delete', [], 'message'));
        }

        return $this->redirectToRoute('profile');
    }
}
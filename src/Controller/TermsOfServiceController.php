<?php

namespace App\Controller;

use App\Entity\TermsOfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("tos", name: "tos_")]
class TermsOfServiceController extends AbstractController
{
    #[Route("/", name: "index")]
    public function index(TranslatorInterface $translator): Response
    {
        $columnDefs = [
            ['key' => $translator->trans('form.entity.termsOfService.label.id', [], 'app'), 'filter' => true, 'sort' => true, 'type' => 'normal'],
            ['key' => $translator->trans('form.entity.termsOfService.label.title', [], 'app'), 'filter' => true, 'sort' => true, 'type' => 'link'],
            ['key' => $translator->trans('form.entity.termsOfService.label.startedAt', [], 'app'), 'filter' => true, 'sort' => true, 'type' => 'normal'],
            ['key' => $translator->trans('form.entity.termsOfService.label.endedAt', [], 'app'), 'filter' => true, 'sort' => true, 'type' => 'normal'],
        ];
        return $this->render('termsOfService/index.html.twig', ['columnDefs' => $columnDefs]);
    }

    #[Route("/new", name: "new")]
    public function new(): Response
    {
        return new Response();
    }

    #[Route("/show/{termsOfService}", name: "show")]
    public function show(TermsOfService $termsOfService): Response
    {
        return new Response();
    }

    #[Route("/edit/{termsOfService}", name: "edit")]
    public function edit(TermsOfService $termsOfService): Response
    {
        return new Response();
    }

    #[Route("/revise/{termsOfService}", name: "revise")]
    public function revise(TermsOfService $termsOfService): Response
    {
        return new Response();
    }
}
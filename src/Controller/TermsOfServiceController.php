<?php

namespace App\Controller;

use App\Entity\TermsOfService;
use App\Form\TermsOfServiceType;
use App\Service\TermsOfServiceService;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("tos", name: "tos_")]
class TermsOfServiceController extends AbstractController
{
    private const AJAX_STATUS_FAIL = 'fail';
    private const AJAX_STATUS_SUCCESS = 'success';

    private EntityManagerInterface $em;
    private TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    #[Route("/", name: "index")]
    #[IsGranted('ROLE_ADMIN')]
    public function index(): Response
    {
        $columnDefs = [
            ['key' => $this->translator->trans('form.entity.termsOfService.label.id', [], 'app'), 'filter' => true, 'sort' => true, 'type' => 'normal'],
            ['key' => $this->translator->trans('form.entity.termsOfService.label.title', [], 'app'), 'filter' => true, 'sort' => true, 'type' => 'link'],
            ['key' => $this->translator->trans('form.entity.termsOfService.label.startedAt', [], 'app'), 'filter' => true, 'sort' => true, 'type' => 'normal'],
            ['key' => $this->translator->trans('form.entity.termsOfService.label.endedAt', [], 'app'), 'filter' => true, 'sort' => true, 'type' => 'normal'],
        ];
        return $this->render('termsOfService/index.html.twig', ['columnDefs' => $columnDefs]);
    }

    #[Route("/new", name: "new")]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, TermsOfServiceService $termsOfServiceService): Response
    {
        $termsOfService = new TermsOfService();
        $form = $this->createForm(TermsOfServiceType::class, $termsOfService);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $termsOfServiceService->create($termsOfService);
            $this->processTranslation($termsOfService, $form);
            $this->addFlash('success', $this->translator->trans('success.termsOfService.new', [], 'message'));
            return $this->redirectToRoute('tos_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('termsOfService/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/show/{termsOfService}", name: "show")]
    public function show(TermsOfService $termsOfService): Response
    {
        return $this->render('termsOfService/show.html.twig', ['termsOfService' => $termsOfService]);
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

    #[Route("/ajax/index", name: "ajax_index", methods: ["GET"])]
    public function ajaxIndex(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['status' => self::AJAX_STATUS_FAIL]);
        }

        $data = [];
        /** @var TermsOfService $termsOfService */
        foreach ($this->em->getRepository(TermsOfService::class)->findAll() as $termsOfService) {
            $data[] = [
                $this->translator->trans('form.entity.termsOfService.label.id', [], 'app') => $termsOfService->getId(),
                $this->translator->trans('form.entity.termsOfService.label.title', [], 'app') => [
                    'text' => $this->translator->trans('termsOfService.format', ['%version%' => $termsOfService->getVersion(), '%revision%' => $termsOfService->getRevision()], 'app'),
                    'url' => $this->generateUrl('tos_show', ['termsOfService' => $termsOfService->getId()])
                ],
                $this->translator->trans('form.entity.termsOfService.label.startedAt', [], 'app') => $termsOfService->getStartedAt()->format('d.m.Y.'),
                $this->translator->trans('form.entity.termsOfService.label.endedAt', [], 'app') => $termsOfService->getEndedAt() !== null ? $termsOfService->getEndedAt()->format('d.m.Y.') : '-',
            ];
        }

        return new JsonResponse(['status' => self::AJAX_STATUS_SUCCESS, 'data' => $data]);
    }

    private function processTranslation(TermsOfService $termsOfService, \Symfony\Component\Form\FormInterface $form)
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $contentAlt = $form->get('contentAlt')->getData();
        $translationRepository->translate($termsOfService, 'content', $localeAlt, $contentAlt);
        $this->em->flush();
    }
}
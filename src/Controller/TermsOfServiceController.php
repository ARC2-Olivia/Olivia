<?php

namespace App\Controller;

use App\Entity\TermsOfService;
use App\Entity\User;
use App\Form\TermsOfServiceType;
use App\Security\TermsOfServiceVoter;
use App\Service\TermsOfServiceService;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/{_locale}/tos", name: "tos_")]
class TermsOfServiceController extends AbstractController
{
    private const AJAX_STATUS_FAIL = 'fail';
    private const AJAX_STATUS_SUCCESS = 'success';

    private ?EntityManagerInterface $em = null;
    private ?TranslatorInterface $translator = null;

    public function __construct(EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->translator = $translator;
    }

    #[Route("/", name: "index")]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var TermsOfService[] $tosList */
        $tosList = $this->em->getRepository(TermsOfService::class)->findBy([], ['id' => 'DESC']);
        return $this->render('termsOfService/index.html.twig', ['tosList' => $tosList]);
    }

    #[Route("/new", name: "new")]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, TermsOfServiceService $termsOfServiceService): Response
    {
        $termsOfService = new TermsOfService();
        $form = $this->createForm(TermsOfServiceType::class, $termsOfService);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $termsOfServiceService->deactivateCurrentlyActive();
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
    #[IsGranted('ROLE_USER')]
    public function show(TermsOfService $termsOfService): Response
    {
        return $this->render('termsOfService/show.html.twig', ['termsOfService' => $termsOfService]);
    }

    #[Route("/active", name: "active")]
    public function active(): Response
    {
        $termsOfService = $this->em->getRepository(TermsOfService::class)->findCurrentlyActive();
        return $this->render('termsOfService/active.html.twig', ['termsOfService' => $termsOfService]);
    }

    #[Route("/edit/{termsOfService}", name: "edit")]
    #[IsGranted(TermsOfServiceVoter::EDIT, subject: "termsOfService")]
    public function edit(TermsOfService $termsOfService, Request $request): Response
    {
        $form = $this->createForm(TermsOfServiceType::class, $termsOfService, ['is_edit' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $tosTitle = $this->translator->trans('termsOfService.format', ['%version%' => $termsOfService->getVersion(), '%revision%' => $termsOfService->getRevision()], 'app');
            $this->addFlash('success', $this->translator->trans('success.termsOfService.edit', ['%termsOfService%' => $tosTitle], 'message'));
            return $this->redirectToRoute('tos_show', ['termsOfService' => $termsOfService->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('termsOfService/edit.html.twig', ['termsOfService' => $termsOfService, 'form' => $form->createView()]);
    }

    #[Route("/revise/{termsOfService}", name: "revise")]
    #[IsGranted(TermsOfServiceVoter::EDIT, subject: "termsOfService")]
    public function revise(TermsOfService $termsOfService, ParameterBagInterface $parameterBag, TermsOfServiceService $termsOfServiceService, Request $request): Response
    {
        list($content, $contentAlt) = $this->extractDefaultAndTranslatedContent($termsOfService, $request);

        $revisedTermsOfService = new TermsOfService();
        $form = $this->createForm(TermsOfServiceType::class, $revisedTermsOfService);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $termsOfServiceService->deactivateCurrentlyActive();
            $termsOfServiceService->revise($revisedTermsOfService);
            $this->processTranslation($revisedTermsOfService, $form);
            $this->addFlash('success', $this->translator->trans('success.termsOfService.revise', [], 'message'));
            return $this->redirectToRoute('tos_index');
        }

        return $this->render('termsOfService/revise.html.twig', [
            'termsOfService' => $termsOfService,
            'form' => $form->createView(),
            'content' => $content,
            'contentAlt' => $contentAlt]
        );
    }

    #[Route("/accept/{termsOfService}", name: "accept")]
    #[IsGranted(TermsOfServiceVoter::ACCEPT, subject: "termsOfService")]
    public function accept(TermsOfService $termsOfService, Request $request, TermsOfServiceService $termsOfServiceService): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('tos.accept', $csrfToken)) {
            /** @var User $user */
            $user = $this->getUser();
            $termsOfServiceService->userAcceptsTermsOfService($user, $termsOfService);
            $this->addFlash('success', $this->translator->trans('success.termsOfService.accept', [], 'message'));
        }
        return $this->redirectToRoute('tos_show', ['termsOfService' => $termsOfService->getId()]);
    }

    #[Route("/rescind/{termsOfService}", name: "rescind")]
    #[IsGranted(TermsOfServiceVoter::RESCIND, subject: "termsOfService")]
    public function rescind(TermsOfService $termsOfService, Request $request, TermsOfServiceService $termsOfServiceService): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('tos.rescind', $csrfToken)) {
            /** @var User $user */
            $user = $this->getUser();
            $termsOfServiceService->userRescindsTermsOfService($user, $termsOfService);
            $this->addFlash('success', $this->translator->trans('success.termsOfService.rescind', [], 'message'));
        }
        return $this->redirectToRoute('tos_active');
    }

    #[Route("/ajax/index", name: "ajax_index", methods: ["GET"])]
    public function ajaxIndex(): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse(['status' => self::AJAX_STATUS_FAIL]);
        }

        $data = [];
        /** @var TermsOfService $termsOfService */
        foreach ($this->em->getRepository(TermsOfService::class)->findBy([], ['id' => 'DESC']) as $termsOfService) {
            $tosTitle = $this->translator->trans('termsOfService.format', ['%version%' => $termsOfService->getVersion(), '%revision%' => $termsOfService->getRevision()], 'app');
            $tosUrl = $this->generateUrl('tos_show', ['termsOfService' => $termsOfService->getId()]);
            $data[] = [
                $this->translator->trans('form.entity.termsOfService.label.id', [], 'app') => $termsOfService->getId(),
                $this->translator->trans('form.entity.termsOfService.label.title', [], 'app') => "<a href='$tosUrl'>$tosTitle</a>",
                $this->translator->trans('form.entity.termsOfService.label.startedAt', [], 'app') => $termsOfService->getStartedAt()->format('d.m.Y.'),
                $this->translator->trans('form.entity.termsOfService.label.endedAt', [], 'app') => $termsOfService->getEndedAt() !== null ? $termsOfService->getEndedAt()->format('d.m.Y.') : '-',
                $this->translator->trans('form.entity.termsOfService.label.active', [], 'app') => $termsOfService->isActive() ? $this->translator->trans('common.yes', [], 'app') : $this->translator->trans('common.no', [], 'app')
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

    private function extractDefaultAndTranslatedContent(TermsOfService $termsOfService, Request $request): array
    {
        $termsOfServiceRepository = $this->em->getRepository(TermsOfService::class);
        $defaultLocale = $this->getParameter('locale.default');
        $alternateLocale = $this->getParameter('locale.alternate');

        $content = $contentAlt = null;
        if ($request->getLocale() === $defaultLocale) {
            $content = $termsOfService->getContent();
            $termsOfService = $termsOfServiceRepository->findByIdForLocale($termsOfService->getId(), $alternateLocale);
            $contentAlt = $termsOfService->getContent();
        } else if ($request->getLocale() === $alternateLocale) {
            $contentAlt = $termsOfService->getContent();
            $termsOfService = $termsOfServiceRepository->findByIdForLocale($termsOfService->getId(), $defaultLocale);
            $content = $termsOfService->getContent();
        }
        return array($content, $contentAlt);
    }
}
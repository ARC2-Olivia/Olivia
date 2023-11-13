<?php

namespace App\Controller;

use App\Entity\DataRequest;
use App\Entity\Gdpr;
use App\Entity\User;
use App\Form\GdprType;
use App\Security\GdprVoter;
use App\Service\DataRequestService;
use App\Service\GdprService;
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

#[Route("/{_locale}/gdpr", name: "gdpr_", requirements: ["_locale" => "%locale.supported%"])]
class GdprController extends AbstractController
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
        /** @var Gdpr[] $gdprs */
        $gdprs = $this->em->getRepository(Gdpr::class)->findBy([], ['id' => 'DESC']);
        return $this->render('termsOfService/index.html.twig', ['gdprs' => $gdprs]);
    }

    #[Route("/new", name: "new")]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, GdprService $gdprService): Response
    {
        $gdpr = new Gdpr();
        $form = $this->createForm(GdprType::class, $gdpr);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $gdprService->deactivateCurrentlyActive();
            $gdprService->create($gdpr);
            $this->processTranslation($gdpr, $form);
            $this->addFlash('success', $this->translator->trans('success.termsOfService.new', [], 'message'));
            return $this->redirectToRoute('gdpr_index');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('termsOfService/new.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/show/{gdpr}", name: "show")]
    #[IsGranted('ROLE_USER')]
    public function show(Gdpr $gdpr): Response
    {
        return $this->render('termsOfService/show.html.twig', ['gdpr' => $gdpr]);
    }

    #[Route("/active", name: "active")]
    public function active(): Response
    {
        $gdpr = $this->em->getRepository(Gdpr::class)->findCurrentlyActive();
        return $this->render('termsOfService/active.html.twig', ['gdpr' => $gdpr]);
    }

    #[Route("/edit/{gdpr}", name: "edit")]
    #[IsGranted(GdprVoter::EDIT, subject: "gdpr")]
    public function edit(Gdpr $gdpr, Request $request): Response
    {
        $form = $this->createForm(GdprType::class, $gdpr, ['is_edit' => true]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $gdprTitle = $this->translator->trans('termsOfService.format', ['%version%' => $gdpr->getVersion(), '%revision%' => $gdpr->getRevision()], 'app');
            $this->addFlash('success', $this->translator->trans('success.termsOfService.edit', ['%termsOfService%' => $gdprTitle], 'message'));
            return $this->redirectToRoute('gdpr_show', ['gdpr' => $gdpr->getId()]);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('termsOfService/edit.html.twig', ['gdpr' => $gdpr, 'form' => $form->createView()]);
    }

    #[Route("/revise/{gdpr}", name: "revise")]
    #[IsGranted(GdprVoter::EDIT, subject: "gdpr")]
    public function revise(Gdpr $gdpr, GdprService $gdprService, Request $request): Response
    {
        list($termsOfService, $termsOfServiceAlt) = $this->extractDefaultAndTranslatedContent($gdpr, $request);

        $revisedGdpr = new Gdpr();
        $form = $this->createForm(GdprType::class, $revisedGdpr);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $gdprService->deactivateCurrentlyActive();
            $gdprService->revise($revisedGdpr);
            $this->processTranslation($revisedGdpr, $form);
            $this->addFlash('success', $this->translator->trans('success.termsOfService.revise', [], 'message'));
            return $this->redirectToRoute('gdpr_index');
        }

        return $this->render('termsOfService/revise.html.twig', [
            'gdpr' => $gdpr,
            'form' => $form->createView(),
            'termsOfService' => $termsOfService,
            'termsOfServiceAlt' => $termsOfServiceAlt
        ]);
    }

    #[Route("/accept/{gdpr}", name: "accept")]
    #[IsGranted(GdprVoter::ACCEPT, subject: "gdpr")]
    public function accept(Gdpr $gdpr, Request $request, GdprService $gdprService): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('tos.accept', $csrfToken)) {
            /** @var User $user */
            $user = $this->getUser();
            $gdprService->userAcceptsGdpr($user, $gdpr);
            $this->addFlash('success', $this->translator->trans('success.termsOfService.accept', [], 'message'));
        }
        return $this->redirectToRoute('gdpr_show', ['gdpr' => $gdpr->getId()]);
    }

    #[Route("/rescind/{gdpr}", name: "rescind")]
    #[IsGranted(GdprVoter::RESCIND, subject: "gdpr")]
    public function rescind(Gdpr $gdpr, Request $request, GdprService $gdprService): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if ($csrfToken !== null && $this->isCsrfTokenValid('tos.rescind', $csrfToken)) {
            /** @var User $user */
            $user = $this->getUser();
            $gdprService->userRescindsGdpr($user, $gdpr);
            $this->addFlash('success', $this->translator->trans('success.termsOfService.rescind', [], 'message'));
        }
        return $this->redirectToRoute('gdpr_active');
    }

    #[Route("/data-protection", name: "data_protection")]
    public function dataProtection(): Response
    {
        return $this->render('gdpr/index.html.twig');
    }

    #[Route("/data-protection/access", name: "data_protection_access")]
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

    #[Route("/data-protection/delete", name: "data_protection_delete")]
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

    #[Route("/data-protection/resolve/{dataRequest}", name: "data_protection_resolve")]
    #[IsGranted('ROLE_ADMIN')]
    public function resolve(DataRequest $dataRequest, DataRequestService $dataRequestService): Response
    {
        $dataRequestService->resolve($dataRequest);
        return $this->redirectToRoute('admin_data_request_index');
    }

    #[Route("/ajax/index", name: "ajax_index", methods: ["GET"])]
    public function ajaxIndex(): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            return new JsonResponse(['status' => self::AJAX_STATUS_FAIL]);
        }

        $data = [];
        /** @var Gdpr $gdpr */
        foreach ($this->em->getRepository(Gdpr::class)->findBy([], ['id' => 'DESC']) as $gdpr) {
            $tosTitle = $this->translator->trans('termsOfService.format', ['%version%' => $gdpr->getVersion(), '%revision%' => $gdpr->getRevision()], 'app');
            $tosUrl = $this->generateUrl('gdpr_show', ['gdpr' => $gdpr->getId()]);
            $data[] = [
                $this->translator->trans('form.entity.termsOfService.label.id', [], 'app') => $gdpr->getId(),
                $this->translator->trans('form.entity.termsOfService.label.title', [], 'app') => "<a href='$tosUrl'>$tosTitle</a>",
                $this->translator->trans('form.entity.termsOfService.label.startedAt', [], 'app') => $gdpr->getStartedAt()->format('d.m.Y.'),
                $this->translator->trans('form.entity.termsOfService.label.endedAt', [], 'app') => $gdpr->getEndedAt() !== null ? $gdpr->getEndedAt()->format('d.m.Y.') : '-',
                $this->translator->trans('form.entity.termsOfService.label.active', [], 'app') => $gdpr->isActive() ? $this->translator->trans('common.yes', [], 'app') : $this->translator->trans('common.no', [], 'app')
            ];
        }

        return new JsonResponse(['status' => self::AJAX_STATUS_SUCCESS, 'data' => $data]);
    }

    private function processTranslation(Gdpr $gdpr, \Symfony\Component\Form\FormInterface $form)
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');
        $termsOfServiceAlt = $form->get('termsOfServiceAlt')->getData();
        $translationRepository->translate($gdpr, 'termsOfService', $localeAlt, $termsOfServiceAlt);
        $this->em->flush();
    }

    private function extractDefaultAndTranslatedContent(Gdpr $gdpr, Request $request): array
    {
        $gdprRepository = $this->em->getRepository(Gdpr::class);
        $defaultLocale = $this->getParameter('locale.default');
        $alternateLocale = $this->getParameter('locale.alternate');

        $termsOfService = $termsOfServiceAlt = null;
        if ($request->getLocale() === $defaultLocale) {
            $termsOfService = $gdpr->getTermsOfService();
            $gdpr = $gdprRepository->findByIdForLocale($gdpr->getId(), $alternateLocale);
            $termsOfServiceAlt = $gdpr->getTermsOfService();
        } else if ($request->getLocale() === $alternateLocale) {
            $termsOfServiceAlt = $gdpr->getTermsOfService();
            $gdpr = $gdprRepository->findByIdForLocale($gdpr->getId(), $defaultLocale);
            $termsOfService = $gdpr->getTermsOfService();
        }
        return array($termsOfService, $termsOfServiceAlt);
    }
}
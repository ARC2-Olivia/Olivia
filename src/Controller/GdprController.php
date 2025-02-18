<?php

namespace App\Controller;

use App\Entity\DataRequest;
use App\Entity\Gdpr;
use App\Entity\User;
use App\Form\DataRequest\DeleteSpecificDataRequestType;
use App\Form\GdprPrivacyPolicyType;
use App\Form\GdprTermsOfServiceType;
use App\Security\GdprVoter;
use App\Service\DataRequestService;
use App\Service\GdprService;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Translatable\Entity\Translation;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/{_locale}/gdpr", name: "gdpr_", requirements: ["_locale" => "%locale.supported%"])]
class GdprController extends BaseController
{
    private const AJAX_STATUS_FAIL = 'fail';
    private const AJAX_STATUS_SUCCESS = 'success';

    #[Route("/", name: "index")]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var Gdpr[] $gdprs */
        $gdprs = $this->em->getRepository(Gdpr::class)->findBy([], ['id' => 'DESC']);
        return $this->render('termsOfService/index.html.twig', ['gdprs' => $gdprs]);
    }

    #[Route("/new", name: "new")]
    #[IsGranted('ROLE_MODERATOR')]
    public function new(Request $request, GdprService $gdprService): Response
    {
        $gdpr = new Gdpr();
        $form = $this->createForm(GdprTermsOfServiceType::class, $gdpr);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $activeGdpr = $this->em->getRepository(Gdpr::class)->findCurrentlyActive();
            list($privacyPolicy, $privacyPolicyAlt) = $this->extractDefaultAndTranslatedPrivacyPolicy($activeGdpr, $request);
            $gdpr->setPrivacyPolicy($privacyPolicy);
            $gdprService->deactivateCurrentlyActive();
            $gdprService->create($gdpr);
            $this->processTermsOfServiceTranslation($gdpr, $form);
            $this->processPrivacyPolicyTranslation([$gdpr], $privacyPolicyAlt);
            $this->addFlash('success', $this->translator->trans('success.termsOfService.new', [], 'message'));
            return $this->redirectToRoute('gdpr_index');
        } else {
            $this->showFormErrorsAsFlashes($form);
        }

        return $this->render('gdpr/newTermsOfService.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/terms-of-service/edit", name: "edit_terms_of_service")]
    public function editTermsOfService(Request $request): Response
    {
        $gdpr = $this->em->getRepository(Gdpr::class)->findCurrentlyActive();
        $this->denyAccessUnlessGranted(GdprVoter::EDIT, $gdpr);

        $form = $this->createForm(GdprTermsOfServiceType::class, $gdpr, ['is_edit' => true]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $gdprTitle = $this->translator->trans('termsOfService.format', ['%version%' => $gdpr->getVersion(), '%revision%' => $gdpr->getRevision()], 'app');
            $this->addFlash('success', $this->translator->trans('success.termsOfService.edit', ['%termsOfService%' => $gdprTitle], 'message'));
            return $this->redirectToRoute('gdpr_active_terms_of_service', ['gdpr' => $gdpr->getId()]);
        } else {
            $this->showFormErrorsAsFlashes($form);
        }

        return $this->render('gdpr/editTermsOfService.htnl.twig', ['gdpr' => $gdpr, 'form' => $form->createView()]);
    }

    #[Route("/terms-of-service/revise", name: "revise_terms_of_service")]
    public function reviseTermsOfService(GdprService $gdprService, Request $request): Response
    {
        $gdpr = $this->em->getRepository(Gdpr::class)->findCurrentlyActive();
        $this->denyAccessUnlessGranted(GdprVoter::EDIT, $gdpr);

        list($termsOfService, $termsOfServiceAlt, $privacyPolicy, $privacyPolicyAlt) = $this->extractDefaultAndTranslatedContent($gdpr, $request);
        $revisedGdpr = new Gdpr();

        $form = $this->createForm(GdprTermsOfServiceType::class, $revisedGdpr);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $revisedGdpr->setPrivacyPolicy($privacyPolicy);
            $gdprService->deactivateCurrentlyActive();
            $gdprService->revise($revisedGdpr);
            $this->processTermsOfServiceTranslation($revisedGdpr, $form);
            $this->processPrivacyPolicyTranslation([$revisedGdpr], $privacyPolicyAlt);
            $this->addFlash('success', $this->translator->trans('success.termsOfService.revise', [], 'message'));
            return $this->redirectToRoute('gdpr_active_terms_of_service');
        } else {
            $this->showFormErrorsAsFlashes($form);
        }

        return $this->render('gdpr/reviseTermsOfService.html.twig', [
            'gdpr' => $gdpr,
            'form' => $form->createView(),
            'termsOfService' => $termsOfService,
            'termsOfServiceAlt' => $termsOfServiceAlt
        ]);
    }

    #[Route("/terms-of-service/active", name: "active_terms_of_service")]
    public function activeTermsOfService(): Response
    {
        $gdpr = $this->em->getRepository(Gdpr::class)->findCurrentlyActive();
        return $this->render('gdpr/activeTermsOfService.html.twig', ['gdpr' => $gdpr, 'tab' => 'termsOfService']);
    }

    #[Route("/terms-of-service/{gdpr}", name: "terms_of_service")]
    #[IsGranted('ROLE_USER')]
    public function termsOfService(Gdpr $gdpr): Response
    {
        if ($gdpr->isActive()) {
            return $this->redirectToRoute('gdpr_active_terms_of_service');
        }
        return $this->render('gdpr/termsOfService.html.twig', ['gdpr' => $gdpr]);
    }

    #[Route("/privacy-policy", name: "privacy_policy")]
    public function privacyPolicy(): Response
    {
        $gdpr = $this->em->getRepository(Gdpr::class)->findCurrentlyActive();
        return $this->render('gdpr/privacyPolicy.html.twig', ['gdpr' => $gdpr, 'tab' => 'privacyPolicy']);
    }

    #[Route("/privacy-policy/edit", name: "edit_privacy_policy")]
    public function editPrivacyPolicy(Request $request): Response
    {
        $activeGdpr = $this->em->getRepository(Gdpr::class)->findCurrentlyActive();
        $this->denyAccessUnlessGranted(GdprVoter::EDIT, $activeGdpr);

        $form = $this->createForm(GdprPrivacyPolicyType::class, $activeGdpr);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $gdprs = $this->em->getRepository(Gdpr::class)->findAll();
            foreach ($gdprs as $gdpr) {
                if ($gdpr->getId() !== $activeGdpr->getId()) {
                    $gdpr->setPrivacyPolicy($activeGdpr->getPrivacyPolicy());
                }
            }
            $this->em->flush();
            $this->addFlash('success', 'Yay');
        } else {
            $this->showFormErrorsAsFlashes($form);
        }

        return $this->render('gdpr/editPrivacyPolicy.html.twig', ['gdpr' => $activeGdpr, 'form' => $form->createView()]);
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
        return $this->redirectToRoute('gdpr_active_terms_of_service', ['gdpr' => $gdpr->getId()]);
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
        return $this->redirectToRoute('gdpr_active_terms_of_service');
    }

    #[Route("/data-protection", name: "data_protection")]
    public function dataProtection(): Response
    {
        $deleteSpecificDataRequestForm = $this->createForm(DeleteSpecificDataRequestType::class, DeleteSpecificDataRequestType::getDefaultData());
        return $this->render('gdpr/index.html.twig', ['deleteSpecificDataRequestForm' => $deleteSpecificDataRequestForm->createView()]);
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

        return $this->redirectToRoute('gdpr_data_protection');
    }

    #[Route("/data-protection/delete", name: "data_protection_delete")]
    public function requestDataDeletion(Request $request): Response
    {
        $csrfToken = $request->request->get('_csrf_token');

        if ($csrfToken !== null && $this->isCsrfTokenValid('data.request.delete', $csrfToken)) {
            $dataRequest = DataRequest::createDeleteRequest($this->getUser());
            $this->em->persist($dataRequest);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.dataRequest.delete', [], 'message'));
        } else {
            $this->addFlash('error', $this->translator->trans('error.dataRequest.delete', [], 'message'));
        }

        return $this->redirectToRoute('gdpr_data_protection');
    }

    #[Route("/data-protection/delete-specific", name: "data_protection_delete_specific")]
    public function requestSpecificDataDeletion(Request $request): Response
    {
        $form = $this->createForm(DeleteSpecificDataRequestType::class, DeleteSpecificDataRequestType::getDefaultData());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $specifics = $form->getData();
            $specifics['other'] = trim($specifics['other']);
            $dataRequest = DataRequest::createDeleteSpecificRequest($this->getUser(), $specifics);
            $this->em->persist($dataRequest);
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.dataRequest.delete', [], 'message'));
        } else {
            $this->addFlash('error', $this->translator->trans('error.dataRequest.delete', [], 'message'));
        }

        return $this->redirectToRoute('gdpr_data_protection');
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
            $tosUrl = $this->generateUrl('gdpr_privacy_policy', ['gdpr' => $gdpr->getId()]);
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

    private function processTermsOfServiceTranslation(Gdpr $gdpr, \Symfony\Component\Form\FormInterface $form): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');

        $termsOfServiceAlt = $form->get('termsOfServiceAlt')->getData();
        $translationRepository->translate($gdpr, 'termsOfService', $localeAlt, $termsOfServiceAlt);

        $this->em->flush();
    }

    /**
     * @param Gdpr[] $gdprs
     * @param string $privacyPolicyAlt
     * @return void
     */
    private function processPrivacyPolicyTranslation(array $gdprs, string $privacyPolicyAlt): void
    {
        $translationRepository = $this->em->getRepository(Translation::class);
        $localeAlt = $this->getParameter('locale.alternate');

        foreach ($gdprs as $gdpr) {
            $translationRepository->translate($gdpr, 'privacyPolicy', $localeAlt, $privacyPolicyAlt);
        }

        $this->em->flush();
    }

    private function extractDefaultAndTranslatedTermsOfService(Gdpr $gdpr, Request $request): array
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

        return [$termsOfService, $termsOfServiceAlt];
    }

    private function extractDefaultAndTranslatedPrivacyPolicy(Gdpr $gdpr, Request $request): array
    {
        $gdprRepository = $this->em->getRepository(Gdpr::class);
        $defaultLocale = $this->getParameter('locale.default');
        $alternateLocale = $this->getParameter('locale.alternate');

        $privacyPolicy = $privacyPolicyAlt = null;
        if ($request->getLocale() === $defaultLocale) {
            $privacyPolicy = $gdpr->getPrivacyPolicy();
            $gdpr = $gdprRepository->findByIdForLocale($gdpr->getId(), $alternateLocale);
            $privacyPolicyAlt = $gdpr->getPrivacyPolicy();
        } else if ($request->getLocale() === $alternateLocale) {
            $privacyPolicyAlt = $gdpr->getPrivacyPolicy();
            $gdpr = $gdprRepository->findByIdForLocale($gdpr->getId(), $defaultLocale);
            $privacyPolicy = $gdpr->getPrivacyPolicy();
        }

        return [$privacyPolicy, $privacyPolicyAlt];
    }

    private function extractDefaultAndTranslatedContent(Gdpr $gdpr, Request $request): array
    {
        $gdprRepository = $this->em->getRepository(Gdpr::class);
        $defaultLocale = $this->getParameter('locale.default');
        $alternateLocale = $this->getParameter('locale.alternate');

        $termsOfService = $termsOfServiceAlt = $privacyPolicy = $privacyPolicyAlt = null;
        if ($request->getLocale() === $defaultLocale) {
            $termsOfService = $gdpr->getTermsOfService();
            $privacyPolicy = $gdpr->getPrivacyPolicy();
            $gdpr = $gdprRepository->findByIdForLocale($gdpr->getId(), $alternateLocale);
            $termsOfServiceAlt = $gdpr->getTermsOfService();
            $privacyPolicyAlt = $gdpr->getPrivacyPolicy();
        } else if ($request->getLocale() === $alternateLocale) {
            $termsOfServiceAlt = $gdpr->getTermsOfService();
            $privacyPolicyAlt = $gdpr->getPrivacyPolicy();
            $gdpr = $gdprRepository->findByIdForLocale($gdpr->getId(), $defaultLocale);
            $termsOfService = $gdpr->getTermsOfService();
            $privacyPolicy = $gdpr->getPrivacyPolicy();
        }

        return [$termsOfService, $termsOfServiceAlt, $privacyPolicy, $privacyPolicyAlt];
    }
}
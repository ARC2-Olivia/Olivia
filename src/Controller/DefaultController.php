<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\File;
use App\Entity\NewsItem;
use App\Entity\Texts;
use App\Entity\User;
use App\Form\ProfileType;
use App\Form\Security\PasswordResetType;
use App\Service\SecurityService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends BaseController
{
    #[Route("/", name: "index_without_locale")]
    public function indexWithoutLocale(): Response
    {
        return $this->redirectToRoute('index', ['_locale' => $this->getParameter('locale.alternate')]);
    }

    #[Route("/{_locale}", name: "index", requirements: ["_locale" => "%locale.supported%"])]
    public function index(Request $request): Response
    {
        $news = $this->em->getRepository(NewsItem::class)->findLatestAmount(6);
        return $this->render('default/index.html.twig', ['news' => $news]);
    }

    #[Route("/{_locale}/profile", name: "profile", requirements: ["_locale" => "%locale.supported%"])]
    public function profile(): Response
    {
        return $this->render('default/profile.html.twig');
    }

    #[Route("/{_locale}/profile/edit/basic-data", name: "profile_edit_basic_data", requirements: ["_locale" => "%locale.supported%"])]
    public function profileEditBasicData(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            $this->addFlash('success', $this->translator->trans('success.user.edit', [], 'message'));
            return $this->redirectToRoute('profile');
        }

        return $this->render('default/edit_profile_basicData.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/{_locale}/profile/edit/password", name: "profile_edit_password", requirements: ["_locale" => "%locale.supported%"])]
    public function profileEditPassword(Request $request, SecurityService $securityService): Response
    {
        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $securityService->changePasswordForUser($this->getUser(), $plainPassword);
            $this->addFlash('success', $this->translator->trans('success.resetPassword.change', [], 'message'));
            return $this->redirectToRoute('profile');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }
        return $this->render('default/edit_profile_password.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/{_locale}/about-us", name: "about_us", requirements: ["_locale" => "%locale.supported%"])]
    public function aboutUs(): Response
    {
        $texts = $this->em->getRepository(Texts::class)->get();
        return $this->render('default/aboutUs.html.twig', ['texts' => $texts]);
    }

    #[Route("/{_locale}/about-project", name: "about_project", requirements: ["_locale" => "%locale.supported%"])]
    public function aboutProject(): Response
    {
        $texts = $this->em->getRepository(Texts::class)->get();
        return $this->render('default/aboutProject.html.twig', ['texts' => $texts]);
    }

    #[Route("/{_locale}/seminars", name: "seminars", requirements: ["_locale" => "%locale.supported%"])]
    #[IsGranted("IS_AUTHENTICATED_FULLY")]
    public function seminars(): Response
    {
        $files = $this->em->getRepository(File::class)->findBy(['seminar' => true]);

        $webinars = array_filter($files, function ($file) { return $file::TYPE_VIDEO === $file->getType(); });
        usort($webinars, function (File $a, File $b) {
            if ($a->getWebinarOrder() === $b->getWebinarOrder()) return 0;
            return $a->getWebinarOrder() > $b->getWebinarOrder() ? 1 : -1;
        });

        $presentations = array_filter($files, function ($file) { return $file::TYPE_FILE === $file->getType(); });
        usort($presentations, function (File $a, File $b) {
            if ($a->getPresentationOrder() === $b->getPresentationOrder()) return 0;
            return $a->getPresentationOrder() > $b->getPresentationOrder() ? 1 : -1;
        });


        return $this->render('default/seminars.html.twig', ['webinars' => $webinars, 'presentations' => $presentations]);
    }

    #[Route("/{_locale}/certificates", name: "certificates", requirements: ["_locale" => "%locale.supported%"])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function certificates(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $courseRepository = $this->em->getRepository(Course::class);

        $courses = [];
        $courses['certified'] = $courseRepository->findPassedByUserAndOrderedByPosition($user);
        $courses['uncertified'] = $courseRepository->findNotPassedByUserAndOrderedByPosition($user);
        $courses['golden'] = null !== $user->getAllCoursesPassedAt();
        return $this->render('default/certificates.html.twig', ['courses' => $courses]);
    }

    #[Route("/{_locale}/api-info", name: "api_info", requirements: ["_locale" => "%locale.supported%"])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function apiInfo(): Response
    {
        return $this->render('default/apiInfo.html.twig');
    }

    #[Route("/{_locale}/apikey/generate", name: "generate_api_key", requirements: ["_locale" => "%locale.supported%"])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function generateApiKey(Request $request, SecurityService $securityService): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (null !== $csrfToken && $this->isCsrfTokenValid('apikey.generate', $csrfToken)) {
            $securityService->generateApiKeyForUser($this->getUser());
            $this->addFlash('success', $this->translator->trans('success.api.generate', [], 'message'));
        }
        return $this->redirectToRoute('profile');
    }

    #[Route("/{_locale}/apikey/delete", name: "delete_api_key", requirements: ["_locale" => "%locale.supported%"])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function deleteApiKey(Request $request, SecurityService $securityService): Response
    {
        $csrfToken = $request->request->get('_csrf_token');
        if (null !== $csrfToken && $this->isCsrfTokenValid('apikey.delete', $csrfToken)) {
            $securityService->deleteApiKeyForUser($this->getUser());
            $this->addFlash('warning', $this->translator->trans('warning.api.delete', [], 'message'));
        }
        return $this->redirectToRoute('profile');
    }

    #[Route("/{_locale}/maintenance", name: "maintenance", requirements: ["_locale" => "%locale.supported%"])]
    public function maintenance(): Response
    {
        return $this->render('default/maintenance.html.twig', ['hideHeader' => true]);
    }

    #[Route("/extend-session", name: "extend_session")]
    public function extendSession(): Response
    {
        return new Response('Session extended.');
    }
}
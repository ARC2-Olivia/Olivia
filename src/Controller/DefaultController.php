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

    #[Route("/{_locale}/webinars", name: "seminars", requirements: ["_locale" => "%locale.supported%"])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function webinars(): Response
    {
        $files = $this->em->getRepository(File::class)->findBy(['seminar' => true], ['webinarOrder' => 'ASC']);
        return $this->render('default/seminars.html.twig', ['files' => $files]);
    }

    #[Route("/{_locale}/presentations", name: "presentations", requirements: ["_locale" => "%locale.supported%"])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function presentation(): Response
    {
        $files = $this->em->getRepository(File::class)->findBy(['presentation' => true], ['presentationOrder' => 'ASC']);
        return $this->render('default/presentations.html.twig', ['files' => $files]);
    }

    #[Route("/{_locale}/certificates", name: "certificates", requirements: ["_locale" => "%locale.supported%"])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function certificates(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $courseRepository = $this->em->getRepository(Course::class);

        $courses = [];
        $courses['certified'] = $courseRepository->findEnrolledByUserAndOrderedByPosition($user);
        $courses['uncertified'] = $courseRepository->findNotEnrolledByUserAndOrderedByPosition($user);
        $courses['golden'] = null !== $user->getAllCoursesCompletedUser();
        return $this->render('default/certificates.html.twig', ['courses' => $courses]);
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
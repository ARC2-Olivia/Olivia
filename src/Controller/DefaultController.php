<?php

namespace App\Controller;

use App\Entity\Texts;
use App\Form\ProfileType;
use App\Form\Security\PasswordResetType;
use App\Service\SecurityService;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends BaseController
{
    #[Route("/", name: "index_without_locale")]
    public function indexWithoutLocale(): Response
    {
        return $this->redirectToRoute('index', ['_locale' => $this->getParameter('locale.default')]);
    }

    #[Route("/{_locale}", name: "index", requirements: ["_locale" => "%locale.supported%"])]
    public function index(): Response
    {
        $package = new Package(new EmptyVersionStrategy());
        $testimonials = [
            ['title' => 'GDPR Fact #1', 'text' => 'GDPR is an EU law with mandatory rules for how organizations and companies must use personal data in an integrity friendly way.'],
            ['title' => 'GDPR Fact #2', 'text' => 'Use of personal data must be respectful to the individuals’ rights, in line with integrity friendly principles and legal.'],
            ['title' => 'GDPR Fact #3', 'text' => 'Personal data means any information which, directly or indirectly, could identify a living person.'],
            ['title' => 'GDPR Fact #4', 'text' => 'The GDPR provides each person with certain rights of their personal data.'],
        ];
        return $this->render('default/index.html.twig', ['testimonials' => $testimonials]);
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

    #[Route("/extend-session", name: "extend_session")]
    public function extendSession(): Response
    {
        return new Response('Session extended.');
    }
}
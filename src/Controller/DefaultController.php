<?php

namespace App\Controller;

use App\Entity\Texts;
use App\Form\ProfileType;
use App\Form\Security\PasswordResetType;
use App\Service\SecurityService;
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
        $testimonials = [
            ['title' => $this->translator->trans('index.facts.1.title', domain: 'app'), 'text' => $this->translator->trans('index.facts.1.text', domain: 'app')],
            ['title' => $this->translator->trans('index.facts.2.title', domain: 'app'), 'text' => $this->translator->trans('index.facts.2.text', domain: 'app')],
            ['title' => $this->translator->trans('index.facts.3.title', domain: 'app'), 'text' => $this->translator->trans('index.facts.3.text', domain: 'app')],
            ['title' => $this->translator->trans('index.facts.4.title', domain: 'app'), 'text' => $this->translator->trans('index.facts.4.text', domain: 'app')]
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
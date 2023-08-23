<?php

namespace App\Controller;

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
            ['title' => 'Testimonial #1', 'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'image' => $package->getUrl('build/images/avatar-empty.svg'), 'personName' => 'John Doe', 'personJob' => 'Job name'],
            ['title' => 'Testimonial #2', 'text' => 'Pellentesque ex sem, tristique ac auctor a, viverra ullamcorper tortor.', 'image' => $package->getUrl('build/images/avatar-empty.svg'), 'personName' => 'John Doe', 'personJob' => 'Job name'],
            ['title' => 'Testimonial #3', 'text' => 'Pellentesque placerat a turpis ut vehicula. In hac habitasse platea dictumst. Aenean nibh ante, eleifend vel lacinia in, pharetra quis elit.', 'image' => $package->getUrl('build/images/avatar-empty.svg'), 'personName' => 'Jane Doe', 'personJob' => 'Job name'],
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
}
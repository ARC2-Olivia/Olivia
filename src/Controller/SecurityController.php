<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Security\LoginType;
use App\Form\Security\RegistrationType;
use App\Security\LoginData;
use App\Security\RegistrationData;
use App\Service\MailerService;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/security", name: "security_")]
class SecurityController extends AbstractController
{
    #[Route("/login", name: "login")]
    public function login(AuthenticationUtils $authenticationUtils, TranslatorInterface $translator): Response
    {
        if ($this->isGranted(User::ROLE_USER)) return $this->redirectToRoute('index');
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error) $this->addFlash('error', $translator->trans('error.login', [], 'message'));
        return $this->render('security/login.html.twig', [
            'form' => $this->createForm(LoginType::class)->createView()
        ]);
    }

    #[Route("/logout", name: "logout")]
    public function logout(): Response
    {
        throw new \LogicException('This method will be intercepted by the firewall.');
    }

    #[Route("/registration", name: "registration")]
    public function registration(Request $request, SecurityService $securityService, MailerService $mailerService, TranslatorInterface $translator): Response
    {
        if ($this->isGranted(User::ROLE_USER)) return $this->redirectToRoute('index');
        $registration = new RegistrationData();
        $form = $this->createForm(RegistrationType::class, $registration);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$securityService->userExists($registration->getEmail())) {
                $user = $securityService->createUnactivatedUser($registration);
                $mailerService->sendConfirmationMail($user);
                $this->addFlash('success', $translator->trans('success.registration', [], 'message'));
            } else {
                $this->addFlash('warning', $translator->trans('warning.registration.userExists', [], 'message'));
            }
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $translator->trans($error->getMessage(), [], 'message'));
            }
        }
        return $this->render('security/registration.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/confirmation/{confirmationToken}", name: "confirmation")]
    public function confirmation(string $confirmationToken, SecurityService $securityService): Response
    {
        $activationSuccess = $securityService->activateUserWithToken($confirmationToken);
        return $this->render('security/confirmation.html.twig', ['activationSuccess' => $activationSuccess]);
    }
}
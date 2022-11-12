<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Security\LoginType;
use App\Form\Security\RegistrationType;
use App\Security\RegistrationData;
use App\Service\MailerService;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route("/security", name: "security_")]
class SecurityController extends AbstractController
{
    #[Route("/login", name: "login")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $form = $this->createForm(LoginType::class);
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastEmail = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
            'lastEmail' => $lastEmail,
            'error' => $error
        ]);
    }

    #[Route("/logout", name: "logout")]
    public function logout(): Response
    {
        throw new \LogicException('This method will be intercepted by the firewall.');
    }

    #[Route("/registration", name: "registration")]
    public function registration(Request $request, SecurityService $securityService, MailerService $mailerService): Response
    {
        $registration = new RegistrationData();
        $form = $this->createForm(RegistrationType::class, $registration);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$securityService->userExists($registration->getEmail())) {
                $user = $securityService->createUnactivatedUser($registration);
                $mailerService->sendConfirmationMail($user);
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
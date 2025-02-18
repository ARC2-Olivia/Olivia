<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\Security\PasswordResetType;
use App\Form\Security\RequestPasswordResetType;
use App\Form\Security\LoginType;
use App\Form\Security\RegistrationType;
use App\Security\RegistrationData;
use App\Service\MailerService;
use App\Service\SecurityService;
use App\Service\GdprService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/{_locale}/security", name: "security_", requirements: ["_locale" => "%locale.supported%"])]
class SecurityController extends AbstractController
{
    private TranslatorInterface $translator;
    private ParameterBagInterface $parameterBag;

    public function __construct(TranslatorInterface $translator, ParameterBagInterface $parameterBag)
    {
        $this->translator = $translator;
        $this->parameterBag = $parameterBag;
    }

    #[Route("/login", name: "login")]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if (!$this->parameterBag->get('registration.enabled')) {
            $this->addFlash('warning', $this->translator->trans('warning.registration.disabled', [], 'message'));
        }

        if ($this->isGranted(User::ROLE_USER)) return $this->redirectToRoute('index');
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error) $this->addFlash('error', $this->translator->trans($error->getMessageKey(), [], 'message'));
        return $this->render('security/login.html.twig', ['form' => $this->createForm(LoginType::class)->createView()]);
    }

    #[Route("/logout", name: "logout")]
    public function logout(): Response
    {
        throw new \LogicException('This method will be intercepted by the firewall.');
    }

    #[Route("/registration", name: "registration")]
    public function registration(Request $request, SecurityService $securityService, MailerService $mailerService): Response
    {
        if (!$this->parameterBag->get('registration.enabled')) {
            return new Response('Access denied.', Response::HTTP_FORBIDDEN);
        }

        if ($this->isGranted(User::ROLE_USER)) return $this->redirectToRoute('index');
        $registration = new RegistrationData();
        $form = $this->createForm(RegistrationType::class, $registration);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$securityService->userExists($registration->getEmail())) {
                $user = $securityService->createUnactivatedUser($registration);
                $mailerService->sendConfirmationMail($user);
                $this->addFlash('success', $this->translator->trans('success.registration', [], 'message'));
            } else {
                $this->addFlash('warning', $this->translator->trans('warning.registration.userExists', [], 'message'));
            }
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }
        return $this->render('security/registration.html.twig', ['form' => $form->createView()]);
    }

    #[Route("/confirmation/{confirmationToken}", name: "confirmation")]
    public function confirmation(string $confirmationToken, SecurityService $securityService, GdprService $termsOfServiceService): Response
    {
        if (!$this->parameterBag->get('registration.enabled')) {
            return new Response('Access denied.', Response::HTTP_FORBIDDEN);
        }

        $user = null;
        $activationSuccess = $securityService->activateUserWithToken($confirmationToken, $user);
        if ($user !== null) $termsOfServiceService->userAcceptsCurrentlyActiveGdpr($user);
        return $this->render('security/confirmation.html.twig', ['activationSuccess' => $activationSuccess]);
    }

    #[Route("/request-password-reset", name: "request_password_reset")]
    public function requestPasswordReset(Request $request, SecurityService $securityService, MailerService $mailerService): Response
    {
        if (!$this->parameterBag->get('registration.enabled')) {
            return new Response('Access denied.', Response::HTTP_FORBIDDEN);
        }

        $requestSent = false;
        $form = $this->createForm(RequestPasswordResetType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $requestSent = true;
            $email = $form->get('email')->getData();
            $user = $securityService->preparePasswordReset($email);
            if (null !== $user) $mailerService->sendPasswordResetMail($user);
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('security/requestPasswordReset.html.twig', ['form' => $form->createView(), 'requestSent' => $requestSent]);
    }

    #[Route("/password-reset/{passwordResetToken}", name: "password_reset")]
    public function passwordReset(string $passwordResetToken, Request $request, SecurityService $securityService): Response
    {
        if (!$this->parameterBag->get('registration.enabled')) {
            return new Response('Access denied.', Response::HTTP_FORBIDDEN);
        }

        if (false === $securityService->passwordResetTokenExists($passwordResetToken)) {
            $this->addFlash('error', $this->translator->trans('error.resetPassword.token.invalid', [], 'message'));
            return $this->redirectToRoute('security_login');
        }

        if (true === $securityService->passwordResetTokenExpired($passwordResetToken)) {
            $this->addFlash('error', $this->translator->trans('error.resetPassword.token.expired', [], 'message'));
            return $this->redirectToRoute('security_login');
        }

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $securityService->changePasswordWithToken($passwordResetToken, $plainPassword);
            $this->addFlash('success', $this->translator->trans('success.resetPassword.change', [], 'message'));
            return $this->redirectToRoute('security_login');
        } else {
            foreach ($form->getErrors(true) as $error) {
                $this->addFlash('error', $this->translator->trans($error->getMessage(), [], 'message'));
            }
        }

        return $this->render('security/passwordReset.html.twig', ['form' => $form->createView()]);
    }
}
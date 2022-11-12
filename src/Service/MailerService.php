<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class MailerService
{
    private ?MailerInterface $mailer = null;
    private ?TranslatorInterface $translator = null;
    private ?ParameterBagInterface $parameterBag = null;
    private ?Environment $twig = null;

    public function __construct(MailerInterface $mailer, TranslatorInterface $translator, ParameterBagInterface $parameterBag, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->translator = $translator;
        $this->parameterBag = $parameterBag;
        $this->twig = $twig;
    }

    public function sendConfirmationMail(User $user): void
    {
        $email = (new Email())
            ->from($this->parameterBag->get('mail.from'))
            ->to($user->getEmail())
            ->subject($this->translator->trans('mail.accountConfirmation.subject', [], 'mail'))
            ->html($this->twig->render('email/accountConfirmation.html.twig', ['user' => $user]))
        ;
        $this->mailer->send($email);
    }
}
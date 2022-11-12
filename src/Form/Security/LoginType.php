<?php

namespace App\Form\Security;

use App\Security\LoginData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class LoginType extends AbstractType
{
    const CSRF_TOKEN_ID = 'authenticate';

    private ?RouterInterface $router = null;
    private ?CsrfTokenManagerInterface $csrfTokenManager = null;

    public function __construct(RouterInterface $router, CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->router = $router;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.security.login.label.email',
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'form.security.login.label.password',
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('_csrf_token', HiddenType::class, [
                'mapped' => false,
                'data' => $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID)
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LoginData::class,
            'translation_domain' => 'app',
            'action' => $this->router->generate('security_login'),
            'method' => 'post'
        ]);
    }
}
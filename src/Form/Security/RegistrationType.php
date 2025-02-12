<?php

namespace App\Form\Security;

use App\Security\RegistrationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationType extends AbstractType
{
    private TranslatorInterface $translator;
    private RouterInterface $router;

    public function __construct(TranslatorInterface $translator, RouterInterface $router)
    {
        $this->translator = $translator;
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => $this->translator->trans('form.security.registration.label.firstName', [], 'app'),
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('lastName', TextType::class, [
                'label' => $this->translator->trans('form.security.registration.label.lastName', [], 'app'),
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('email', EmailType::class, [
                'label' => $this->translator->trans('form.security.registration.label.email', [], 'app'),
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => ['attr' => ['class' => 'form-input mb-3']],
                'invalid_message' => 'error.registration.password.repeated',
                'first_options' => ['label' => $this->translator->trans('form.security.registration.label.password', [], 'app')],
                'second_options' => ['label' => $this->translator->trans('form.security.registration.label.confirmPassword', [], 'app')]
            ])
            ->add('acceptedGdpr', CheckboxType::class, [
                'label' => $this->translator->trans('form.security.registration.label.termsOfService', ['%url%' => $this->router->generate('gdpr_active_terms_of_service')], 'app')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegistrationData::class,
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}
<?php

namespace App\Form\Security;

use App\Security\RegistrationData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'form.security.registration.label.email',
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => ['attr' => ['class' => 'form-input mb-3']],
                'invalid_message' => 'error.registration.password.repeated',
                'first_options' => ['label' => 'form.security.registration.label.password'],
                'second_options' => ['label' => 'form.security.registration.label.confirmPassword']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => RegistrationData::class,
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}
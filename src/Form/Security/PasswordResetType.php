<?php

namespace App\Form\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PasswordResetType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => ['attr' => ['class' => 'form-input mb-3']],
                'invalid_message' => 'error.resetPassword.password.repeated',
                'first_options' => ['label' => $this->translator->trans('form.security.resetPassword.label.password', [], 'app')],
                'second_options' => ['label' => $this->translator->trans('form.security.resetPassword.label.confirmPassword', [], 'app')],
                'constraints' => [new Assert\NotBlank(['message' => 'error.resetPassword.password.blank'])]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'translation_domain' => 'app',
            'method' => 'post',
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}

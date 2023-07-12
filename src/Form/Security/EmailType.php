<?php

namespace App\Form\Security;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType as CoreEmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', CoreEmailType::class, [
                'label' => 'form.security.resetPassword.label.email',
                'attr' => ['class' => 'form-input mb-3'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'error.resetPassword.email.blank']),
                    new Assert\Email(['message' => 'error.resetPassword.email.format'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'translation_domain' => 'app',
            'method' => 'post',
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}
<?php

namespace App\Form;

use App\Entity\Gdpr;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class GdprPrivacyPolicyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('privacyPolicy', HiddenType::class, [
                'label' => 'form.entity.termsOfService.label.privacyPolicy',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'error.termsOfService.privacyPolicy'])
                ]
            ])
            ->add('privacyPolicyAlt', HiddenType::class, [
                'label' => 'form.entity.termsOfService.label.privacyPolicyAlt',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'error.termsOfService.privacyPolicyAlt'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}

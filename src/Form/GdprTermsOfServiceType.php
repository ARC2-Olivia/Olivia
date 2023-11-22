<?php

namespace App\Form;

use App\Entity\Gdpr;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class GdprTermsOfServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('termsOfService', HiddenType::class, [
                'label' => 'form.entity.termsOfService.label.termsOfService',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'error.termsOfService.termsOfService'])
                ]
            ])
        ;

        if ($options['is_edit'] !== true) {
            $builder
                ->add('termsOfServiceAlt', HiddenType::class, [
                    'label' => 'form.entity.termsOfService.label.termsOfServiceAlt',
                    'mapped' => false,
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'error.termsOfService.termsOfServiceAlt'])
                    ]
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gdpr::class,
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate'],
            'is_edit' => false
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\TermsOfService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TermsOfServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', HiddenType::class, [
            'label' => 'form.entity.termsOfService.label.content',
            'constraints' => [
                new Assert\NotBlank(['message' => 'error.termsOfService.content'])
            ]
        ]);

        if ($options['is_edit'] !== true) {
            $builder->add('contentAlt', HiddenType::class, [
                'label' => 'form.entity.termsOfService.label.contentAlt',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'error.termsOfService.contentAlt'])
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TermsOfService::class,
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate'],
            'is_edit' => false
        ]);
    }
}

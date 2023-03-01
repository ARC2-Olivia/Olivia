<?php

namespace App\Form;

use App\Entity\PracticalSubmodule;
use App\Form\Transformer\SimpleArrayToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalSubmoduleType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.entity.evaluation.label.name',
                'attr' => [
                    'class' => 'form-input mb-3',
                    'placeholder' => $this->translator->trans('form.entity.evaluation.placeholder.name', [], 'app')
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.entity.evaluation.label.description',
                'attr' => [
                    'class' => 'form-textarea mb-3',
                    'placeholder' => $this->translator->trans('form.entity.evaluation.placeholder.description', [], 'app')
                ]
            ])
            ->add('tags', TextareaType::class, [
                'label' => 'form.entity.evaluation.label.tags',
                'attr' => [
                    'class' => 'form-input mb-3',
                    'placeholder' => $this->translator->trans('form.entity.evaluation.placeholder.tags', [], 'app')
                ]
            ])
        ;
        $builder->get('tags')->addModelTransformer(new SimpleArrayToStringTransformer());

        if ($options['include_translatable_fields']) {
            $builder
                ->add('nameAlt', TextType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.evaluation.label.nameAlt',
                    'attr' => [
                        'class' => 'form-input mb-3',
                        'placeholder' => $this->translator->trans('form.entity.evaluation.placeholder.name', [], 'app')
                    ]
                ])
                ->add('descriptionAlt', TextareaType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.evaluation.label.descriptionAlt',
                    'attr' => [
                        'class' => 'form-textarea mb-3',
                        'placeholder' => $this->translator->trans('form.entity.evaluation.placeholder.description', [], 'app')
                    ]
                ])
                ->add('tagsAlt', TextareaType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.evaluation.label.tagsAlt',
                    'attr' => [
                        'class' => 'form-input mb-3',
                        'placeholder' => $this->translator->trans('form.entity.evaluation.placeholder.tags', [], 'app')
                    ]
                ])
            ;
            $builder->get('tagsAlt')->addModelTransformer(new SimpleArrayToStringTransformer());
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmodule::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false,
            'attr' => [
                'class' => 'd-flex flex-column',
                'novalidate' => 'novalidate'
            ]
        ]);
    }
}

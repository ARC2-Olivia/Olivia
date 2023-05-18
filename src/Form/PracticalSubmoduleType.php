<?php

namespace App\Form;

use App\Entity\PracticalSubmodule;
use App\Form\Transformer\SimpleArrayToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

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
                'label' => 'form.entity.practicalSubmodule.label.name',
                'attr' => [
                    'class' => 'form-input mb-3',
                    'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.name', [], 'app')
                ]
            ])
            ->add('image', FileType::class, [
                'label' => 'form.entity.practicalSubmodule.label.image',
                'attr' => ['class' => 'form-input mb-3'],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'error.practicalSubmodule.image.maxSize',
                        'mimeTypes' => 'image/*',
                        'mimeTypesMessage' => 'error.practicalSubmodule.image.mimeType'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.entity.practicalSubmodule.label.description',
                'attr' => [
                    'class' => 'form-textarea mb-3',
                    'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.description', [], 'app')
                ]
            ])
            ->add('tags', TextareaType::class, [
                'label' => 'form.entity.practicalSubmodule.label.tags',
                'attr' => [
                    'class' => 'form-input mb-3',
                    'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.tags', [], 'app')
                ]
            ])
        ;
        $builder->get('tags')->addModelTransformer(new SimpleArrayToStringTransformer());

        if ($options['include_translatable_fields']) {
            $builder
                ->add('nameAlt', TextType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.practicalSubmodule.label.nameAlt',
                    'attr' => [
                        'class' => 'form-input mb-3',
                        'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.name', [], 'app')
                    ]
                ])
                ->add('descriptionAlt', TextareaType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.practicalSubmodule.label.descriptionAlt',
                    'attr' => [
                        'class' => 'form-textarea mb-3',
                        'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.description', [], 'app')
                    ]
                ])
                ->add('tagsAlt', TextareaType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.practicalSubmodule.label.tagsAlt',
                    'attr' => [
                        'class' => 'form-input mb-3',
                        'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.tags', [], 'app')
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

<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\PracticalSubmodule;
use App\Form\Custom\WorkloadType;
use App\Form\Transformer\SimpleArrayToStringTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CourseType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $allowedWorkloadTimes = [
            $this->translator->trans('form.entity.course.choices.estimatedWorkload.hours', [], 'app')  => 'H',
            $this->translator->trans('form.entity.course.choices.estimatedWorkload.days', [], 'app')   => 'D',
            $this->translator->trans('form.entity.course.choices.estimatedWorkload.weeks', [], 'app')  => 'W',
            $this->translator->trans('form.entity.course.choices.estimatedWorkload.months', [], 'app') => 'M',
            $this->translator->trans('form.entity.course.choices.estimatedWorkload.years', [], 'app')  => 'Y',
        ];
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.entity.course.label.name',
                'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.course.placeholder.name', [], 'app')]
            ])
            ->add('image', FileType::class,  [
                'label' => 'form.entity.course.label.image',
                'attr' => ['class' => 'form-input mb-3'],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'error.course.image.maxSize',
                        'mimeTypes' => 'image/*',
                        'mimeTypesMessage' => 'error.course.image.mimeType'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.entity.course.label.description',
                'attr' => ['class' => 'form-textarea mb-3', 'placeholder' => $this->translator->trans('form.entity.course.placeholder.description', [], 'app')],
            ])
            ->add('estimatedWorkload', WorkloadType::class, [
                'label' => 'form.entity.course.label.estimatedWorkload',
                'allowed_workload_times' => $allowedWorkloadTimes,
            ])
            ->add('tags', TextType::class, [
                'label' => 'form.entity.course.label.tags',
                'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.course.placeholder.tags', [], 'app')]
            ])
            ->add('practicalSubmodules', EntityType::class, [
                'class' => PracticalSubmodule::class,
                'label' => 'form.entity.course.label.practicalSubmodules',
                'choice_label' => 'name',
                'attr' => ['class' => 'mb-3', 'data-df-select' => ''],
                'multiple' => true
            ])
        ;
        $builder->get('tags')->addModelTransformer(new SimpleArrayToStringTransformer());

        if ($options['include_translatable_fields']) {
            $builder
                ->add('nameAlt', TextType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.course.label.nameAlt',
                    'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.course.placeholder.name', [], 'app')]
                ])
                ->add('descriptionAlt', TextareaType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.course.label.descriptionAlt',
                    'attr' => ['class' => 'form-textarea mb-3', 'placeholder' => $this->translator->trans('form.entity.course.placeholder.description', [], 'app')],
                ])

                ->add('tagsAlt', TextType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.course.label.tagsAlt',
                    'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.course.placeholder.tags', [], 'app')]
                ])
            ;
            $builder->get('tagsAlt')->addModelTransformer(new SimpleArrayToStringTransformer());
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false,
            'attr' => [
                'class' => 'd-flex flex-column',
                'novalidate' => 'novalidate'
            ]
        ]);
    }
}

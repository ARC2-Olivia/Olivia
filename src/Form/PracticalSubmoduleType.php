<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\PracticalSubmodule;
use App\Form\Transformer\SimpleArrayToStringTransformer;
use App\Service\PracticalSubmoduleService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
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
        $booleanChoices = [
            $this->translator->trans('common.no', [], 'app') => false,
            $this->translator->trans('common.yes', [], 'app') => true
        ];

        $opModeChoices = [
            $this->translator->trans('practicalSubmodule.modeOfOperation.simple', domain: 'app') => PracticalSubmodule::MODE_OF_OPERATION_SIMPLE,
            $this->translator->trans('practicalSubmodule.modeOfOperation.advanced', domain: 'app') => PracticalSubmodule::MODE_OF_OPERATION_ADVANCED
        ];

        $exportTypeChoices = [
            $this->translator->trans('practicalSubmodule.exportType.none', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_NONE,
            $this->translator->trans('practicalSubmodule.exportType.simple', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_SIMPLE,
            $this->translator->trans('practicalSubmodule.exportType.privacyPolicy', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_PRIVACY_POLICY,
            $this->translator->trans('practicalSubmodule.exportType.consentPersonalDataProcessing', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_PERSONAL_DATA_PROCESSING_CONSENT,
            $this->translator->trans('practicalSubmodule.exportType.cookieBanner', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_COOKIE_BANNER,
            $this->translator->trans('practicalSubmodule.exportType.cookiePolicy', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_COOKIE_POLICY,
            $this->translator->trans('practicalSubmodule.exportType.lia', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_LIA,
            $this->translator->trans('practicalSubmodule.exportType.dpia', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_DPIA,
            $this->translator->trans('practicalSubmodule.exportType.respondentsRight', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_RESPONDENTS_RIGHTS,
            $this->translator->trans('practicalSubmodule.exportType.rulebookOnISS', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_RULEBOOK_ON_ISS,
            $this->translator->trans('practicalSubmodule.exportType.rulebookOnPDP', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_RULEBOOK_ON_PDP,
            $this->translator->trans('practicalSubmodule.exportType.contractBetweenControllerAndProcessor', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_CONTROLLER_PROCESSOR_CONTRACT,
            $this->translator->trans('practicalSubmodule.exportType.videoSurveillanceNotification', domain: 'app') => PracticalSubmodule::EXPORT_TYPE_VIDEO_SURVEILLANCE_NOTIFICATION,
        ];

        $builder
            ->add('name', TextType::class, [
                'label' => 'form.entity.practicalSubmodule.label.name',
                'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.name', [], 'app')]
            ])
            ->add('publicName', TextType::class, [
                'label' => 'form.entity.practicalSubmodule.label.publicName',
                'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.publicName', [], 'app')]
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
                'attr' => ['class' => 'form-textarea mb-3', 'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.description', [], 'app')]
            ])
            ->add('exportType', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmodule.label.exportType',
                'choices' => $exportTypeChoices,
                'attr' => ['class' => 'form-select mb-3'],
                'disabled' => true === $options['has_advanced_mode_features']
            ])
            ->add('paging', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmodule.label.paging',
                'choices' => $booleanChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('modeOfOperation', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmodule.label.modeOfOperation',
                'choices' => $opModeChoices,
                'attr' => ['class' => 'form-select mb-3'],
                'disabled' => true === $options['has_advanced_mode_features']
            ])
            ->add('tags', TextType::class, [
                'label' => 'form.entity.practicalSubmodule.label.tags',
                'attr' => [
                    'class' => 'form-input mb-3',
                    'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.tags', [], 'app')
                ]
            ])
            ->add('courses', EntityType::class, [
                'class' => Course::class,
                'label' => 'form.entity.practicalSubmodule.label.courses',
                'choice_label' => 'name',
                'attr' => ['class' => 'mb-3', 'data-df-select' => ''],
                'multiple' => true
            ])
            ->add('reportComment', TextareaType::class, [
                'label' => 'form.entity.practicalSubmodule.label.reportComment',
                'attr' => [
                    'class' => 'form-textarea mb-3',
                    'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.reportComment', [], 'app')
                ],
            ])
            ->add('revisionMode', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmodule.label.revisionMode',
                'choices' => $booleanChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('position', NumberType::class, [
                'html5' => true,
                'label' => 'form.entity.practicalSubmodule.label.position',
                'attr' => ['class' => 'form-input mb-3', 'inputmode' => 'numeric']
            ])
        ;
        $builder->get('tags')->addModelTransformer(new SimpleArrayToStringTransformer());

        if ($options['include_translatable_fields']) {
            $builder
                ->add('nameAlt', TextType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.practicalSubmodule.label.nameAlt',
                    'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.name', [], 'app')]
                ])
                ->add('publicNameAlt', TextType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.practicalSubmodule.label.publicNameAlt',
                    'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.publicName', [], 'app')]
                ])
                ->add('descriptionAlt', TextareaType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.practicalSubmodule.label.descriptionAlt',
                    'attr' => ['class' => 'form-textarea mb-3', 'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.description', [], 'app')]
                ])
                ->add('tagsAlt', TextareaType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.practicalSubmodule.label.tagsAlt',
                    'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.tags', [], 'app')]
                ])
                ->add('reportCommentAlt', TextareaType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.practicalSubmodule.label.reportCommentAlt',
                    'attr' => ['class' => 'form-textarea mb-3', 'placeholder' => $this->translator->trans('form.entity.practicalSubmodule.placeholder.reportComment', [], 'app')],
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
            'has_advanced_mode_features' => false,
            'attr' => [
                'class' => 'd-flex flex-column',
                'novalidate' => 'novalidate'
            ]
        ]);
    }
}

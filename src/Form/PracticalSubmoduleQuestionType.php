<?php

namespace App\Form;

use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalSubmoduleQuestionType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (false === $options['edit_mode']) {
            $typeChoices = [
                $this->translator->trans('practicalSubmoduleQuestion.type.yesNo', [], 'app') => PracticalSubmoduleQuestion::TYPE_YES_NO,
                $this->translator->trans('practicalSubmoduleQuestion.type.weighted', [], 'app') => PracticalSubmoduleQuestion::TYPE_WEIGHTED,
                $this->translator->trans('practicalSubmoduleQuestion.type.numericalInput', [], 'app') => PracticalSubmoduleQuestion::TYPE_NUMERICAL_INPUT,
                $this->translator->trans('practicalSubmoduleQuestion.type.textInput', [], 'app') => PracticalSubmoduleQuestion::TYPE_TEXT_INPUT,
                $this->translator->trans('practicalSubmoduleQuestion.type.templatedTextInput', [], 'app') => PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT,
                $this->translator->trans('practicalSubmoduleQuestion.type.multipleChoice', [], 'app') => PracticalSubmoduleQuestion::TYPE_MULTI_CHOICE,
                $this->translator->trans('practicalSubmoduleQuestion.type.listInput', [], 'app') => PracticalSubmoduleQuestion::TYPE_LIST_INPUT,
                $this->translator->trans('practicalSubmoduleQuestion.type.staticText', [], 'app') => PracticalSubmoduleQuestion::TYPE_STATIC_TEXT
            ];
            $builder->add('type', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.type',
                'choices' => $typeChoices,
                'attr' => ['class' => 'form-select mb-3']
            ]);
        }

        $booleanChoices = [
            $this->translator->trans('common.no', [], 'app') => false,
            $this->translator->trans('common.yes', [], 'app') => true
        ];
        $builder
            ->add('evaluable', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.evaluable',
                'choices' => $booleanChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('disabled', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.disabled',
                'choices' => $booleanChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('dependentPracticalSubmoduleQuestion', EntityType::class, [
                'class' => PracticalSubmoduleQuestion::class,
                'label' => 'form.entity.practicalSubmoduleQuestion.label.dependentQuestion',
                'query_builder' => $this->makeDependentQuestionQueryBuilder($builder),
                'choice_label' => 'questionText',
                'placeholder' => 'form.entity.practicalSubmoduleQuestion.placeholder.dependentEvaluationQuestion',
                'attr' => ['class' => 'mb-3', 'data-df-select' => '']
            ])
            ->add('dependentValue', TextType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.dependentValue',
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('questionText', TextareaType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.questionText',
                'attr' => [
                    'class' => 'form-textarea mb-3',
                    'placeholder' => $this->translator->trans('form.entity.practicalSubmoduleQuestion.placeholder.questionText', [], 'app')
                ]
            ])
        ;

        if (true === $options['include_other_field']) {
            $builder->add('otherEnabled', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.otherEnabled',
                'choices' => $booleanChoices,
                'attr' => ['class' => 'form-select mb-3']
            ]);
        }

        if (true === $options['include_multiple_weighted_field']) {
            $builder->add('multipleWeighted', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.multipleWeighted',
                'choices' => $booleanChoices,
                'attr' => ['class' => 'form-select mb-3']
            ]);
        }

        if (true === $options['include_translatable_fields']) {
            $builder->add('questionTextAlt', TextareaType::class, [
                'mapped' => false,
                'label' => 'form.entity.practicalSubmoduleQuestion.label.questionTextAlt',
                'attr' => [
                    'class' => 'form-textarea mb-3',
                    'placeholder' => $this->translator->trans('form.entity.practicalSubmoduleQuestion.placeholder.questionText', [], 'app')
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmoduleQuestion::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false,
            'edit_mode' => false,
            'include_other_field' => false,
            'include_multiple_weighted_field' => false,
            'attr' => [
                'class' => 'd-flex flex-column',
                'novalidate' => 'novalidate'
            ]
        ]);
    }

    private function makeDependentQuestionQueryBuilder(FormBuilderInterface $builder): \Closure|null
    {
        /** @var PracticalSubmoduleQuestion $practicalSubmoduleQuestion */
        $practicalSubmoduleQuestion = $builder->getData();
        $practicalSubmodule = $practicalSubmoduleQuestion?->getPracticalSubmodule();

        $queryBuilder = null;
        if ($practicalSubmoduleQuestion !== null && $practicalSubmodule !== null) {
            $queryBuilder = function (EntityRepository $repository) use ($practicalSubmoduleQuestion) {
                $qb = $repository->createQueryBuilder('psq')
                    ->where('psq.practicalSubmodule = :submodule')->andWhere('psq.type NOT IN (:types)')
                    ->setParameters([
                        'submodule' => $practicalSubmoduleQuestion->getPracticalSubmodule(),
                        'types' => [PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT, PracticalSubmoduleQuestion::TYPE_STATIC_TEXT]
                    ])
                ;

                if ($practicalSubmoduleQuestion->getId() !== null) {
                    $qb->andWhere('psq != :question')->setParameter('question', $practicalSubmoduleQuestion);
                }

                return $qb;
            };
        }
        return $queryBuilder;
    }
}

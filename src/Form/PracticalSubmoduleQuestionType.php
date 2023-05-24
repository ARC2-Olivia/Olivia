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
        if ($options['edit_mode'] === false) {
            $typeChoices = [
                $this->translator->trans('practicalSubmoduleQuestion.type.yesNo', [], 'app') => PracticalSubmoduleQuestion::TYPE_YES_NO,
                $this->translator->trans('practicalSubmoduleQuestion.type.weighted', [], 'app') => PracticalSubmoduleQuestion::TYPE_WEIGHTED,
                $this->translator->trans('practicalSubmoduleQuestion.type.numericalInput', [], 'app') => PracticalSubmoduleQuestion::TYPE_NUMERICAL_INPUT,
                $this->translator->trans('practicalSubmoduleQuestion.type.textInput', [], 'app') => PracticalSubmoduleQuestion::TYPE_TEXT_INPUT,
                $this->translator->trans('practicalSubmoduleQuestion.type.templatedTextInput', [], 'app') => PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT,
                $this->translator->trans('practicalSubmoduleQuestion.type.multipleChoice', [], 'app') => PracticalSubmoduleQuestion::TYPE_MULTI_CHOICE,
            ];
            $builder->add('type', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.type',
                'choices' => $typeChoices,
                'attr' => ['class' => 'form-select mb-3']
            ]);
        }

        $evaluableChoices = [
            $this->translator->trans('common.no', [], 'app') => false,
            $this->translator->trans('common.yes', [], 'app') => true
        ];
        $builder
            ->add('evaluable', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.evaluable',
                'choices' => $evaluableChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('dependentPracticalSubmoduleQuestion', EntityType::class, [
                'class' => PracticalSubmoduleQuestion::class,
                'label' => 'form.entity.practicalSubmoduleQuestion.label.dependentQuestion',
                'query_builder' => $this->makeDependentQuestionQueryBuilder($builder),
                'choice_label' => 'questionText',
                'placeholder' => 'form.entity.practicalSubmoduleQuestion.placeholder.dependentEvaluationQuestion',
                'attr' => ['class' => 'form-select mb-3']
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

        if ($options['include_translatable_fields']) {
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
                    ->where('psq.practicalSubmodule = :submodule')->andWhere('psq.type != :type')
                    ->setParameters(['submodule' => $practicalSubmoduleQuestion->getPracticalSubmodule(), 'type' => PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT])
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

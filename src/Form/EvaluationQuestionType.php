<?php

namespace App\Form;

use App\Entity\EvaluationQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EvaluationQuestionType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeChoices = [
            $this->translator->trans('evaluationQuestion.type.yesNo', [], 'app') => EvaluationQuestion::TYPE_YES_NO,
            $this->translator->trans('evaluationQuestion.type.weighted', [], 'app') => EvaluationQuestion::TYPE_WEIGHTED,
            $this->translator->trans('evaluationQuestion.type.numericalInput', [], 'app') => EvaluationQuestion::TYPE_NUMERICAL_INPUT
        ];

        $evaluatableChoices = [
            $this->translator->trans('common.no', [], 'app') => false,
            $this->translator->trans('common.yes', [], 'app') => true
        ];

        $builder
            ->add('type', ChoiceType::class, [
                'label' => 'form.entity.evaluationQuestion.label.type',
                'choices' => $typeChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('evaluable', ChoiceType::class, [
                'label' => 'form.entity.evaluationQuestion.label.evaluatable',
                'choices' => $evaluatableChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('questionText', TextareaType::class, [
                'label' => 'form.entity.evaluationQuestion.label.questionText',
                'attr' => [
                    'class' => 'form-textarea mb-3',
                    'placeholder' => $this->translator->trans('form.entity.evaluationQuestion.placeholder.questionText', [], 'app')
                ]
            ])
        ;

        if ($options['include_translatable_fields']) {
            $builder->add('questionTextAlt', TextareaType::class, [
                'mapped' => false,
                'label' => 'form.entity.evaluationQuestion.label.questionTextAlt',
                'attr' => [
                    'class' => 'form-textarea mb-3',
                    'placeholder' => $this->translator->trans('form.entity.evaluationQuestion.placeholder.questionText', [], 'app')
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EvaluationQuestion::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false,
            'attr' => [
                'class' => 'd-flex flex-column',
                'novalidate' => 'novalidate'
            ]
        ]);
    }
}

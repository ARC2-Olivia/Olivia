<?php

namespace App\Form;

use App\Entity\EvaluationQuestion;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
        if ($options['edit_mode'] === false) {
            $typeChoices = [
                $this->translator->trans('evaluationQuestion.type.yesNo', [], 'app') => EvaluationQuestion::TYPE_YES_NO,
                $this->translator->trans('evaluationQuestion.type.weighted', [], 'app') => EvaluationQuestion::TYPE_WEIGHTED,
                $this->translator->trans('evaluationQuestion.type.numericalInput', [], 'app') => EvaluationQuestion::TYPE_NUMERICAL_INPUT
            ];
            $builder->add('type', ChoiceType::class, [
                'label' => 'form.entity.evaluationQuestion.label.type',
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
                'label' => 'form.entity.evaluationQuestion.label.evaluable',
                'choices' => $evaluableChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('dependentEvaluationQuestion', EntityType::class, [
                'class' => EvaluationQuestion::class,
                'label' => 'form.entity.evaluationQuestion.label.dependentQuestion',
                'query_builder' => $this->makeDependentQuestionQueryBuilder($builder),
                'choice_label' => 'questionText',
                'placeholder' => 'form.entity.evaluationQuestion.placeholder.dependentEvaluationQuestion',
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('dependentValue', TextType::class, [
                'label' => 'form.entity.evaluationQuestion.label.dependentValue',
                'attr' => ['class' => 'form-input mb-3']
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
            'edit_mode' => false,
            'attr' => [
                'class' => 'd-flex flex-column',
                'novalidate' => 'novalidate'
            ]
        ]);
    }

    private function makeDependentQuestionQueryBuilder(FormBuilderInterface $builder): \Closure|null
    {
        /** @var EvaluationQuestion $evaluationQuestion */
        $evaluationQuestion = $builder->getData();
        $queryBuilder = null;
        if ($evaluationQuestion !== null) {
            $queryBuilder = function (EntityRepository $repository) use ($evaluationQuestion) {
                return $repository->createQueryBuilder('eq')
                    ->where('eq != :evaluationQuestion')
                    ->andWhere('eq.evaluation = :evaluation')
                    ->setParameters(['evaluationQuestion' => $evaluationQuestion, 'evaluation' => $evaluationQuestion->getEvaluation()])
                ;
            };
        }
        return $queryBuilder;
    }
}

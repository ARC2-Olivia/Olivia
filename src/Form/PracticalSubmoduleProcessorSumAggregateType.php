<?php

namespace App\Form;

use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorSumAggregate;
use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalSubmoduleProcessorSumAggregateType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('evaluationQuestions', EntityType::class, [
                'class' => PracticalSubmoduleQuestion::class,
                'label' => 'form.entity.evaluationEvaluator.label.evaluationQuestion',
                'choice_label' => 'questionText',
                'query_builder' => $this->makeEvaluationQuestionQueryBuilder($builder),
                'attr' => ['class' => 'form-select multiple mb-3'],
                'multiple' => true
            ])
            ->add('evaluationEvaluators', EntityType::class, [
                'class' => PracticalSubmoduleProcessor::class,
                'label' => 'form.entity.evaluationEvaluator.label.evaluationEvaluators',
                'choice_label' => 'name',
                'query_builder' => $this->makeEvaluationEvaluatorQueryBuilder($builder),
                'attr' => ['class' => 'form-select multiple mb-3'],
                'multiple' => true
            ])
            ->add('expectedValueRangeStart', NumberType::class, [
                'label' => 'form.entity.evaluationEvaluator.label.expectedValueRange.start',
                'html5' => true,
                'attr' => ['class' => 'form-input mb-3', 'step' => 0.01]
            ])
            ->add('expectedValueRangeEnd', NumberType::class, [
                'label' => 'form.entity.evaluationEvaluator.label.expectedValueRange.end',
                'html5' => true,
                'attr' => ['class' => 'form-input mb-3', 'step' => 0.01]
            ])
            ->add('resultText', TextareaType::class, [
                'label' => 'form.entity.evaluationEvaluator.label.resultText',
                'attr' => [
                    'class' => 'form-textarea mb-3',
                    'placeholder' => $this->translator->trans('form.entity.evaluationEvaluator.placeholder.resultText', [], 'app')
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmoduleProcessorSumAggregate::class,
            'translation_domain' => 'app',
            'attr' => [
                'novalidate' => 'novalidate',
                'class' => 'd-flex flex-column'
            ]
        ]);
    }

    private function makeEvaluationQuestionQueryBuilder(FormBuilderInterface $builder): ?\Closure
    {
        $evaluationEvaluator = $builder->getData()?->getEvaluationEvaluator();
        $evaluationQuestionQueryBuilder = null;
        if ($evaluationEvaluator !== null) {
            $evaluationQuestionQueryBuilder = function (EntityRepository $repository) use ($evaluationEvaluator) {
                return $repository->createQueryBuilder('eq')
                    ->where('eq.evaluation = :evaluation')
                    ->andWhere('eq.evaluable = :evaluable')
                    ->andWhere('eq.type IN (:types)')
                    ->setParameters(['evaluation' => $evaluationEvaluator->getEvaluation(), 'evaluable' => true, 'types' => PracticalSubmoduleQuestion::getNumericTypes()]);
            };
        }
        return $evaluationQuestionQueryBuilder;
    }

    private function makeEvaluationEvaluatorQueryBuilder(FormBuilderInterface $builder): ?\Closure
    {
        $evaluationEvaluator = $builder->getData()?->getEvaluationEvaluator();
        $evaluationEvaluatorQueryBuilder = null;
        if ($evaluationEvaluator !== null) {
            $evaluationEvaluatorQueryBuilder = function (EntityRepository $repository) use ($evaluationEvaluator) {
                return $repository->createQueryBuilder('ee')
                    ->where('ee != :evaluationEvaluator')
                    ->andWhere('ee.evaluation = :evaluation')
                    ->setParameters(['evaluationEvaluator' => $evaluationEvaluator, 'evaluation' => $evaluationEvaluator->getEvaluation()]);
            };
        }
        return $evaluationEvaluatorQueryBuilder;
    }
}

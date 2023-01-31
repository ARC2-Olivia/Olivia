<?php

namespace App\Form;

use App\Entity\EvaluationEvaluator;
use App\Entity\EvaluationEvaluatorSumAggregate;
use App\Entity\EvaluationQuestion;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EvaluationEvaluatorSumAggregateType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $evaluationEvaluator = $builder->getData()?->getEvaluationEvaluator();
        $evaluationQuestionQueryBuilder = null;
        if ($evaluationEvaluator !== null) {
            $evaluationQuestionQueryBuilder = function (EntityRepository $repository) use ($evaluationEvaluator) {
                return $repository->createQueryBuilder('eq')
                    ->where('eq.evaluation = :evaluation')
                    ->andWhere('eq.evaluable = :evaluable')
                    ->setParameters(['evaluation' => $evaluationEvaluator->getEvaluation(), 'evaluable' => true]);
            };
        }

        $builder
            ->add('evaluationQuestions', EntityType::class, [
                'class' => EvaluationQuestion::class,
                'label' => 'form.entity.evaluationEvaluator.label.evaluationQuestion',
                'choice_label' => 'questionText',
                'query_builder' => $evaluationQuestionQueryBuilder,
                'attr' => ['class' => 'form-select multiple mb-3'],
                'multiple' => true
            ])
            ->add('expectedValueRangeStart', NumberType::class, [
                'label' => 'form.entity.evaluationEvaluator.label.expectedValueRange.start',
                'html5' => true,
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('expectedValueRangeEnd', NumberType::class, [
                'label' => 'form.entity.evaluationEvaluator.label.expectedValueRange.end',
                'html5' => true,
                'attr' => ['class' => 'form-input mb-3']
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
            'data_class' => EvaluationEvaluatorSumAggregate::class,
            'translation_domain' => 'app',
            'attr' => [
                'novalidate' => 'novalidate',
                'class' => 'd-flex flex-column'
            ]
        ]);
    }
}

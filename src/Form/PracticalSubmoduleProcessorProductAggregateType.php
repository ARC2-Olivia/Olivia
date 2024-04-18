<?php

namespace App\Form;

use App\Entity\File;
use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorProductAggregate;
use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalSubmoduleProcessorProductAggregateType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PracticalSubmoduleProcessor $practicalSubmoduleProcessor */
        $practicalSubmoduleProcessor = $builder->getData()?->getPracticalSubmoduleProcessor();
        $modeOfOperation = $practicalSubmoduleProcessor?->getPracticalSubmodule()->getModeOfOperation();
        $included = $practicalSubmoduleProcessor?->isIncluded();
        $simpleMode = true === $included && PracticalSubmodule::MODE_OF_OPERATION_SIMPLE === $modeOfOperation;

        if (true === $simpleMode) {
            $builder->add('question', EntityType::class, [
                'class' => PracticalSubmoduleQuestion::class,
                'label' => 'form.entity.practicalSubmoduleProcessor.label.evaluationQuestion',
                'choice_label' => 'questionText',
                'query_builder' => $this->makePracticalSubmoduleQuestionQueryBuilder($builder),
                'attr' => ['class' => 'mb-3', 'data-df-select' => ''],
                'mapped' => false,
                'data' => $practicalSubmoduleProcessor?->getPracticalSubmoduleProcessorProductAggregate()?->getPracticalSubmoduleQuestions()?->get(0),
                'placeholder' => $this->translator->trans('form.entity.practicalSubmoduleProcessor.placeholder.evaluationQuestion', [], 'app')
            ]);
        } else {
            $builder->add('questions', EntityType::class, [
                'class' => PracticalSubmoduleQuestion::class,
                'label' => 'form.entity.practicalSubmoduleProcessor.label.evaluationQuestion',
                'choice_label' => 'questionText',
                'query_builder' => $this->makePracticalSubmoduleQuestionQueryBuilder($builder),
                'attr' => ['class' => 'mb-3', 'data-df-select' => ''],
                'multiple' => true,
                'mapped' => false,
                'data' => $practicalSubmoduleProcessor?->getPracticalSubmoduleProcessorProductAggregate()?->getPracticalSubmoduleQuestions()
            ]);
        }

        $builder
            ->add('practicalSubmoduleProcessors', EntityType::class, [
                'class' => PracticalSubmoduleProcessor::class,
                'label' => 'form.entity.practicalSubmoduleProcessor.label.evaluationEvaluators',
                'choice_label' => 'name',
                'query_builder' => $this->makePracticalSubmoduleProcessorQueryBuilder($builder),
                'attr' => ['class' => 'mb-3', 'data-df-select' => ''],
                'multiple' => true
            ])
            ->add('expectedValueRangeStart', NumberType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessor.label.expectedValueRange.start',
                'html5' => true,
                'attr' => ['class' => 'form-input mb-3', 'step' => 0.01]
            ])
            ->add('expectedValueRangeEnd', NumberType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessor.label.expectedValueRange.end',
                'html5' => true,
                'attr' => ['class' => 'form-input mb-3', 'step' => 0.01]
            ])
            ->add('resultText', TextareaType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessor.label.resultText',
                'attr' => [
                    'class' => 'form-textarea mb-3',
                    'placeholder' => $this->translator->trans('form.entity.practicalSubmoduleProcessor.placeholder.resultText', [], 'app')
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmoduleProcessorProductAggregate::class,
            'translation_domain' => 'app',
            'attr' => [
                'novalidate' => 'novalidate',
                'class' => 'd-flex flex-column'
            ]
        ]);
    }

    private function makePracticalSubmoduleQuestionQueryBuilder(FormBuilderInterface $builder): ?\Closure
    {
        /** @var PracticalSubmoduleProcessor $practicalSubmoduleProcessor */
        $practicalSubmoduleProcessor = $builder->getData()?->getPracticalSubmoduleProcessor();
        $evaluationQuestionQueryBuilder = null;
        if ($practicalSubmoduleProcessor !== null) {
            $evaluationQuestionQueryBuilder = function (EntityRepository $repository) use ($practicalSubmoduleProcessor) {
                return $repository->createQueryBuilder('psq')
                    ->where('psq.practicalSubmodule = :submodule')
                    ->andWhere('psq.evaluable = :evaluable')
                    ->andWhere('psq.type IN (:types)')
                    ->setParameters(['submodule' => $practicalSubmoduleProcessor->getPracticalSubmodule(), 'evaluable' => true, 'types' => PracticalSubmoduleQuestion::getNumericTypes()])
                    ->orderBy('psq.position', 'ASC')
                ;
            };
        }
        return $evaluationQuestionQueryBuilder;
    }

    private function makePracticalSubmoduleProcessorQueryBuilder(FormBuilderInterface $builder): ?\Closure
    {
        /** @var PracticalSubmoduleProcessor $practicalSubmoduleProcessor */
        $practicalSubmoduleProcessor = $builder->getData()?->getPracticalSubmoduleProcessor();
        $evaluationEvaluatorQueryBuilder = null;
        if ($practicalSubmoduleProcessor !== null) {
            $evaluationEvaluatorQueryBuilder = function (EntityRepository $repository) use ($practicalSubmoduleProcessor) {
                return $repository->createQueryBuilder('psp')
                    ->where('psp != :processor')
                    ->andWhere('psp.practicalSubmodule = :submodule')
                    ->setParameters(['processor' => $practicalSubmoduleProcessor, 'submodule' => $practicalSubmoduleProcessor->getPracticalSubmodule()])
                    ->orderBy('psp.position', 'ASC')
                ;
            };
        }
        return $evaluationEvaluatorQueryBuilder;
    }
}

<?php

namespace App\Form;

use App\Entity\File;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorMaxValue;
use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalSubmoduleProcessorMaxValueType extends AbstractType
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

        $qbQuestion = function (EntityRepository $repository) use ($practicalSubmoduleProcessor) {
            return $repository->createQueryBuilder('psq')
                ->where('psq.practicalSubmodule = :submodule')
                ->andWhere('psq.evaluable = :evaluable')
                ->andWhere('psq.type IN (:types)')
                ->setParameters(['submodule' => $practicalSubmoduleProcessor->getPracticalSubmodule(), 'evaluable' => true, 'types' => PracticalSubmoduleQuestion::getNumericTypes()])
                ->orderBy('psq.position', 'ASC')
            ;
        };

        $qbProcessor = function (EntityRepository $repository) use ($practicalSubmoduleProcessor) {
            return $repository->createQueryBuilder('psp')
                ->where('psp != :processor')
                ->andWhere('psp.practicalSubmodule = :submodule')
                ->setParameters(['processor' => $practicalSubmoduleProcessor, 'submodule' => $practicalSubmoduleProcessor->getPracticalSubmodule()])
                ->orderBy('psp.position', 'ASC')
            ;
        };

        $builder
            ->add('practicalSubmoduleQuestions', EntityType::class, [
                'class' => PracticalSubmoduleQuestion::class,
                'label' => 'form.entity.practicalSubmoduleProcessor.label.evaluationQuestions',
                'choice_label' => 'questionText',
                'multiple' => true,
                'query_builder' => $qbQuestion,
                'attr' => ['class' => 'mb-3', 'data-df-select' => '']
            ])
            ->add('practicalSubmoduleProcessors', EntityType::class, [
                'class' => PracticalSubmoduleProcessor::class,
                'label' => 'form.entity.practicalSubmoduleProcessor.label.evaluationEvaluators',
                'choice_label' => 'name',
                'multiple' => true,
                'query_builder' => $qbProcessor,
                'attr' => ['class' => 'mb-3', 'data-df-select' => '']
            ])
            ->add('expectedValueRangeStart', NumberType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessor.label.expectedValueRange.start',
                'html5' => true,
                'attr' => ['class' => 'form-input mb-3', 'step' => 0.01]
            ])
            ->add('expectedValueRangeEnd', NumberType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessor.label.expectedValueRange.start',
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
            'data_class' => PracticalSubmoduleProcessorMaxValue::class,
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate', 'class' => 'd-flex flex-column']
        ]);
    }
}

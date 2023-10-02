<?php

namespace App\Form;

use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalSubmoduleProcessorType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeChoices = [
            $this->translator->trans('practicalSubmoduleProcessor.type.simple', [], 'app') => PracticalSubmoduleProcessor::TYPE_SIMPLE,
            $this->translator->trans('practicalSubmoduleProcessor.type.sumAggregate', [], 'app') => PracticalSubmoduleProcessor::TYPE_SUM_AGGREGATE,
            $this->translator->trans('practicalSubmoduleProcessor.type.productAggregate', [], 'app') => PracticalSubmoduleProcessor::TYPE_PRODUCT_AGGREGATE,
            $this->translator->trans('practicalSubmoduleProcessor.type.templatedText', [], 'app') => PracticalSubmoduleProcessor::TYPE_TEMPLATED_TEXT,
            $this->translator->trans('practicalSubmoduleProcessor.type.html', [], 'app') => PracticalSubmoduleProcessor::TYPE_HTML,
            $this->translator->trans('practicalSubmoduleProcessor.type.resultCombiner', [], 'app') => PracticalSubmoduleProcessor::TYPE_RESULT_COMBINER,
            $this->translator->trans('practicalSubmoduleProcessor.type.maxValue', [], 'app') => PracticalSubmoduleProcessor::TYPE_MAX_VALUE
        ];

        $booleanChoices = [
            $this->translator->trans('common.no', [], 'app') => false,
            $this->translator->trans('common.yes', [], 'app') => true
        ];

        if (false === $options['edit_mode']) {
            $builder->add('type', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessor.label.type',
                'choices' => $typeChoices,
                'attr' => ['class' => 'form-select mb-3']
            ]);
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessor.label.name',
                'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.practicalSubmoduleProcessor.placeholder.name', [], 'app')]
            ])
            ->add('included', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessor.label.included',
                'choices' => $booleanChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('disabled', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessor.label.disabled',
                'choices' => $booleanChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
            ->add('dependentPracticalSubmoduleQuestion', EntityType::class, [
                'class' => PracticalSubmoduleQuestion::class,
                'label' => 'form.entity.practicalSubmoduleProcessor.label.dependentQuestion',
                'query_builder' => $this->makeDependentQuestionQueryBuilder($builder),
                'choice_label' => 'questionText',
                'placeholder' => 'form.entity.practicalSubmoduleProcessor.placeholder.evaluationQuestion',
                'attr' => ['class' => 'mb-3', 'data-df-select' => '']
            ])
            ->add('dependentValue', TextType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessor.label.dependentValue',
                'attr' => ['class' => 'form-input mb-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmoduleProcessor::class,
            'translation_domain' => 'app',
            'edit_mode' => false,
            'attr' => [
                'novalidate' => 'novalidate',
                'class' => 'd-flex flex-column'
            ]
        ]);
    }

    private function makeDependentQuestionQueryBuilder(FormBuilderInterface $builder)
    {
        /** @var PracticalSubmoduleProcessor $practicalSubmoduleProcessor */
        $practicalSubmoduleProcessor = $builder->getData();
        $practicalSubmodule = $practicalSubmoduleProcessor?->getPracticalSubmodule();

        $queryBuilder = null;
        if ($practicalSubmoduleProcessor !== null && $practicalSubmodule !== null) {
            $queryBuilder = function (EntityRepository $repository) use ($practicalSubmodule) {
                $qb = $repository->createQueryBuilder('psq')
                    ->where('psq.practicalSubmodule = :submodule')->andWhere('psq.type NOT IN (:types)')
                    ->setParameters([
                        'submodule' => $practicalSubmodule,
                        'types' => [PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT, PracticalSubmoduleQuestion::TYPE_STATIC_TEXT]
                    ])
                ;
                return $qb;
            };
        }
        return $queryBuilder;
    }
}

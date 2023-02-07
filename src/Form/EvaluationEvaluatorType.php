<?php

namespace App\Form;

use App\Entity\EvaluationEvaluator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EvaluationEvaluatorType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeChoices = [
            $this->translator->trans('evaluationEvaluator.type.simple', [], 'app') => EvaluationEvaluator::TYPE_SIMPLE,
            $this->translator->trans('evaluationEvaluator.type.sumAggregate', [], 'app') => EvaluationEvaluator::TYPE_SUM_AGGREGATE,
            $this->translator->trans('evaluationEvaluator.type.productAggregate', [], 'app') => EvaluationEvaluator::TYPE_PRODUCT_AGGREGATE
        ];

        $includedChoices = [
            $this->translator->trans('common.no', [], 'app') => false,
            $this->translator->trans('common.yes', [], 'app') => true
        ];

        if ($options['edit_mode'] === false) {
            $builder->add('type', ChoiceType::class, [
                'label' => 'form.entity.evaluationEvaluator.label.type',
                'choices' => $typeChoices,
                'attr' => ['class' => 'form-select mb-3']
            ]);
        }

        $builder
            ->add('name', TextType::class, [
                'label' => 'form.entity.evaluationEvaluator.label.name',
                'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.evaluationEvaluator.placeholder.name', [], 'app')]
            ])
            ->add('included', ChoiceType::class, [
                'label' => 'form.entity.evaluationEvaluator.label.included',
                'choices' => $includedChoices,
                'attr' => ['class' => 'form-select mb-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EvaluationEvaluator::class,
            'translation_domain' => 'app',
            'edit_mode' => false,
            'attr' => [
                'novalidate' => 'novalidate',
                'class' => 'd-flex flex-column'
            ]
        ]);
    }
}

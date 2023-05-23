<?php

namespace App\Form;

use App\Entity\PracticalSubmoduleProcessor;
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
            $this->translator->trans('practicalSubmoduleProcessor.type.templatedText', [], 'app') => PracticalSubmoduleProcessor::TYPE_TEMPLATED_TEXT
        ];

        $includedChoices = [
            $this->translator->trans('common.no', [], 'app') => false,
            $this->translator->trans('common.yes', [], 'app') => true
        ];

        if ($options['edit_mode'] === false) {
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
                'choices' => $includedChoices,
                'attr' => ['class' => 'form-select mb-3']
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
}

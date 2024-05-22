<?php

namespace App\Form;

use App\Entity\PracticalSubmoduleQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalSubmoduleQuestionTemplatedListInputType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $booleanChoices = [
            $this->translator->trans('common.no', [], 'app') => false,
            $this->translator->trans('common.yes', [], 'app') => true
        ];

        $builder
            ->add('isModal', ChoiceType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.isModal',
                'choices' => $booleanChoices,
                'attr' => ['class' => 'mb-3', 'data-df-select' => '']
            ])
            ->add('template', TextareaType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestion.label.template',
                'attr' => ['class' => 'form-textarea mb-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmoduleQuestion::class,
            'translation_domain' => 'app',
            'attr' => ['class' => 'd-flex flex-column', 'novalidate' => 'novalidate']
        ]);
    }
}
<?php

namespace App\Form;

use App\Entity\PracticalSubmoduleQuestionAnswer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PracticalSubmoduleQuestionAnswerWeightedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('answerText', TextareaType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestionAnswer.label.weighted.answerText',
                'attr' => ['class' => 'form-textarea mb-3', 'step' => 0.01]
            ])
            ->add('answerValue', NumberType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestionAnswer.label.weighted.answerValue',
                'html5' => true,
                'attr' => ['class' => 'form-input mb-3', 'step' => 0.01]
            ])
        ;

        if ($options['include_translatable_fields']) {
            $builder->add('answerTextAlt', TextareaType::class, [
                'mapped' => false,
                'label' => 'form.entity.practicalSubmoduleQuestionAnswer.label.weighted.answerTextAlt',
                'attr' => ['class' => 'form-textarea mb-3']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmoduleQuestionAnswer::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false,
            'attr' => ['novalidate' => 'novalidate', 'class' => 'd-flex flex-column']
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\EvaluationQuestionAnswer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvaluationQuestionAnswerWeightedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('answerText', TextareaType::class, [
                'label' => 'form.entity.evaluationQuestionAnswer.label.weighted.answerText',
                'attr' => ['class' => 'form-textarea mb-3']
            ])
            ->add('answerValue', NumberType::class, [
                'label' => 'form.entity.evaluationQuestionAnswer.label.weighted.answerValue',
                'html5' => true,
                'attr' => ['class' => 'form-input mb-3']
            ])
        ;

        if ($options['include_translatable_fields']) {
            $builder->add('answerTextAlt', TextareaType::class, [
                'mapped' => false,
                'label' => 'form.entity.evaluationQuestionAnswer.label.weighted.answerTextAlt',
                'attr' => ['class' => 'form-textarea mb-3']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EvaluationQuestionAnswer::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false,
            'attr' => ['novalidate' => 'novalidate', 'class' => 'd-flex flex-column']
        ]);
    }
}

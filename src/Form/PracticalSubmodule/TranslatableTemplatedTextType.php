<?php

namespace App\Form\PracticalSubmodule;

use App\Entity\PracticalSubmoduleQuestionAnswer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TranslatableTemplatedTextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextareaType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestionAnswer.label.templatedText.answerText',
                'attr' => ['class' => 'form-textarea mb-3']
            ])
            ->add('translatedText', TextareaType::class, [
                'label' => 'form.entity.practicalSubmoduleQuestionAnswer.label.templatedText.answerTextAlt',
                'attr' => ['class' => 'form-textarea mb-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TranslatableTemplatedText::class,
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate', 'class' => 'd-flex flex-column']
        ]);
    }
}

<?php

namespace App\Form\Quiz;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuizType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('answers', CollectionType::class, ['entry_type' => QuizAnswerType::class]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['attr' => ['novalidate' => 'novalidate', 'class' => 'lesson-quiz-quiz'], 'allow_extra_fields' => true]);
    }
}

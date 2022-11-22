<?php

namespace App\Form;

use App\Entity\Lesson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LessonType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.entity.lesson.label.name',
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.entity.lesson.label.description',
                'attr' =>['class' => 'form-textarea mb-3']
            ])
            ->add('type', HiddenType::class, ['data' => $options['lesson_type']]);
        ;

        if ($options['lesson_type'] === Lesson::TYPE_TEXT) {
            $builder->add('content', HiddenType::class, ['mapped' => false]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate'],
            'lesson_type' => Lesson::TYPE_TEXT
        ]);
    }
}

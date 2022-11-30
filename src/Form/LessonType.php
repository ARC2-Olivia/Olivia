<?php

namespace App\Form;

use App\Entity\Lesson;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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
            $builder->add('content', HiddenType::class, [
                'label' => 'form.entity.lesson.label.content',
                'mapped' => false
            ]);
        } else if ($options['lesson_type'] === Lesson::TYPE_FILE) {
            $builder->add('file', FileType::class, [
                'label' => 'form.entity.lesson.label.file',
                'mapped' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '20M',
                        'maxSizeMessage' => 'error.lesson.file.size',
                        'uploadNoFileErrorMessage' => 'error.lesson.file.noFile'
                    ])
                ],
                'attr' => ['class' => 'form-input mb-3']
            ]);
        } else if ($options['lesson_type'] === Lesson::TYPE_VIDEO) {
            $builder->add('video', TextType::class, [
                'label' => 'form.entity.lesson.label.video',
                'mapped' => false,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'error.lesson.video.blank'])
                ],
                'attr' => ['class' => 'form-input mb-3']
            ]);
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

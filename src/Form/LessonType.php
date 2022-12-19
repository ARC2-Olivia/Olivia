<?php

namespace App\Form;

use App\Entity\Lesson;
use App\Entity\LessonItemEmbeddedVideo;
use App\Entity\LessonItemFile;
use App\Entity\LessonItemQuiz;
use App\Entity\LessonItemText;
use App\Exception\InvalidLessonTypeAndLessonItemCombinationException;
use App\Exception\UnsupportedLessonTypeException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LessonType extends AbstractType
{
    /**
     * @throws UnsupportedLessonTypeException
     * @throws InvalidLessonTypeAndLessonItemCombinationException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var LessonItemText|LessonItemFile|LessonItemEmbeddedVideo|LessonItemQuiz $lessonItem */
        $lessonItem = $options['lesson_item'];
        $lessonType = $options['lesson_type'];

        if (!in_array($lessonType, Lesson::getSupportedLessonTypes())) {
            throw UnsupportedLessonTypeException::withDefaultMessage();
        }

        if ($lessonItem !== null) {
            if ($lessonType === Lesson::TYPE_TEXT && !($lessonItem instanceof LessonItemText)) throw InvalidLessonTypeAndLessonItemCombinationException::forTextLessonType($lessonItem::class);
            if ($lessonType === Lesson::TYPE_FILE && !($lessonItem instanceof LessonItemFile)) throw InvalidLessonTypeAndLessonItemCombinationException::forFileLessonType($lessonItem::class);
            if ($lessonType === Lesson::TYPE_VIDEO && !($lessonItem instanceof LessonItemEmbeddedVideo)) throw InvalidLessonTypeAndLessonItemCombinationException::forVideoLessonType($lessonItem::class);
            if ($lessonType === Lesson::TYPE_QUIZ && !($lessonItem instanceof LessonItemQuiz)) throw InvalidLessonTypeAndLessonItemCombinationException::forQuizLessonType($lessonItem::class);
        }

        $builder
            ->add('name', TextType::class, ['label' => 'form.entity.lesson.label.name', 'attr' => ['class' => 'form-input mb-3']])
            ->add('description', TextareaType::class, ['label' => 'form.entity.lesson.label.description', 'attr' => ['class' => 'form-textarea mb-3']])
            ->add('type', HiddenType::class, ['data' => $options['lesson_type']]);
        ;

        if ($lessonType === Lesson::TYPE_TEXT) {
            $builder->add('text', HiddenType::class, ['mapped' => false, 'label' => 'form.entity.lesson.label.text', 'data' => $lessonItem?->getText()]);
        } else if ($lessonType === Lesson::TYPE_FILE) {
            $builder->add('file', FileType::class, [
                'mapped' => false,
                'label' => 'form.entity.lesson.label.file',
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '20M',
                        'maxSizeMessage' => 'error.lesson.file.size',
                        'uploadNoFileErrorMessage' => 'error.lesson.file.noFile'
                    ])
                ],
                'attr' => ['class' => 'form-input mb-3'],
            ]);
        } else if ($lessonType === Lesson::TYPE_VIDEO) {
            $builder->add('video', TextType::class, [
                'mapped' => false,
                'label' => 'form.entity.lesson.label.video',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'error.lesson.video.blank'])
                ],
                'attr' => ['class' => 'form-input mb-3'],
                'data' => $lessonItem?->getVideoUrl()
            ]);
        } else if ($lessonType === Lesson::TYPE_QUIZ) {
            $builder->add('passingPercentage', RangeType::class, [
                'mapped' => false,
                'label' => 'form.entity.lesson.label.passingPercentage',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'error.lesson.quiz.blank']),
                    new Assert\Range(['min' => 0, 'max' => 100, 'notInRangeMessage' => 'error.lesson.quiz.range'])
                ],
                'attr' => ['class' => 'form-input mb-3', 'min' => 0, 'max' => 100],
                'data' => $lessonItem?->getPassingPercentage()
            ]);
        }

        if ($options['include_translatable_fields']) {
            $builder
                ->add('nameAlt', TextType::class, ['mapped' => false, 'label' => 'form.entity.lesson.label.nameAlt', 'attr' => ['class' => 'form-input mb-3']])
                ->add('descriptionAlt', TextareaType::class, ['mapped' => false, 'label' => 'form.entity.lesson.label.descriptionAlt', 'attr' => ['class' => 'form-textarea mb-3']])
            ;
            if ($lessonType === Lesson::TYPE_TEXT) $builder->add('textAlt', HiddenType::class, ['mapped' => false, 'label' => 'form.entity.lesson.label.textAlt']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate'],
            'include_translatable_fields' => false,
            'lesson_type' => Lesson::TYPE_TEXT,
            'lesson_item' => null
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\Instructor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class InstructorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'form.entity.instructor.label.firstName',
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'form.entity.instructor.label.lastName',
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.entity.instructor.label.email',
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('institution', TextType::class, [
                'label' => 'form.entity.instructor.label.institution',
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('image', FileType::class, [
                'label' => 'form.entity.instructor.label.image',
                'attr' => ['class' => 'form-input mb-3'],
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'maxSizeMessage' => 'error.course.image.maxSize',
                        'mimeTypes' => 'image/*',
                        'mimeTypesMessage' => 'error.course.image.mimeType'
                    ])
                ]
            ])
            ->add('biography', TextareaType::class, [
                'label' => 'form.entity.instructor.label.biography',
                'attr' => ['class' => 'form-textarea mb-3']
            ])
        ;

        if ($options['include_translatable_fields']) {
            $builder
                ->add('institutionAlt', TextType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.instructor.label.institutionAlt',
                    'attr' => ['class' => 'form-input mb-3']
                ])
                ->add('biographyAlt', TextareaType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.instructor.label.biographyAlt',
                    'attr' => ['class' => 'form-textarea mb-3']
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Instructor::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false,
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}

<?php

namespace App\Form;

use App\Entity\Instructor;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class CourseInstructorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('instructor', EntityType::class, [
                'class' => Instructor::class,
                'choices' => $options['instructors'],
                'choice_label' => function (Instructor $instructor) {
                    $label = $instructor->getFirstName() . ' ' . $instructor->getLastName();
                    if ($instructor->getEmail()) $label .= ' (' . $instructor->getEmail() . ')';
                    return $label;
                },
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'error.instructor.notNull'
                    ])
                ],
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'form.entity.course.placeholder.instructor'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'common.submit',
                'attr' => ['class' => 'btn btn-theme-white bg-blue']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'mapped' => false,
            'translation_domain' => 'app',
            'instructors' => null,
            'attr' => [
                'class' => 'row mt-5',
                'novalidate' => 'novalidate'
            ]
        ]);
    }
}
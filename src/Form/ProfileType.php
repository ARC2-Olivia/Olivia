<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'form.entity.user.label.firstName',
                'attr' => ['class' => 'form-input']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'form.entity.user.label.lastName',
                'attr' => ['class' => 'form-input']
            ])
            ->add('affiliation', TextareaType::class, [
                'label' => 'form.entity.user.label.affiliation',
                'attr' => ['class' => 'form-textarea']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate', 'class' => 'row g-3']
        ]);
    }
}

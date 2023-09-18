<?php

namespace App\Form;

use App\Entity\Topic;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TopicType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'form.entity.topic.label.title',
                'attr' => ['class' => 'form-input mb-3']
            ])
        ;

        if (true === $options['include_translatable_fields']) {
            $builder
                ->add('title', TextType::class, [
                    'label' => 'form.entity.topic.label.title',
                    'attr' => ['class' => 'form-input mb-3']
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Topic::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false,
            'attr' => [
                'class' => 'd-flex flex-column',
                'novalidate' => 'novalidate'
            ]
        ]);
    }
}

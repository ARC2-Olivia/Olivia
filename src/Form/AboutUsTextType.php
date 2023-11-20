<?php

namespace App\Form;

use App\Entity\Texts;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AboutUsTextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('aboutUs', HiddenType::class, ['label' => 'form.entity.texts.label.aboutUs']);
        if (true === $options['include_translatable_fields']) {
            $builder->add('aboutUsAlt', HiddenType::class, ['label' => 'form.entity.texts.label.aboutUsAlt', 'mapped' => false]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Texts::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false
        ]);
    }
}

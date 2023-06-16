<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class BasicFileUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fileOptions = [
            'label' => 'common.file',
            'attr' => ['class' => 'form-input mb-3'],
        ];

        if (null !== $options['mimeTypes']) {
            $fileOptions['constraints'] = [
                new Assert\File(['mimeTypes' => $options['mimeTypes']])
            ];
        }

        if (null !== $options['extensions']) {
            $fileOptions['attr']['accept'] = $options['extensions'];
        }

        $builder->add('file', FileType::class, $fileOptions);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'app',
            'attr' => ['class' => 'd-flex flex-column'],
            'mimeTypes' => null,
            'extensions' => null
        ]);
    }
}

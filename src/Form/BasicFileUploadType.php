<?php

namespace App\Form;

use App\Entity\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

class BasicFileUploadType extends AbstractType
{
    private ?TranslatorInterface $translator = null;
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fileOptions = [
            'label' => 'common.file',
            'attr' => ['class' => 'form-input mb-3'],
            'required' => $options['requiredFile']
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
        if (true === $options['selectType']) {
            $typeChoices = [$this->translator->trans('file.type.file', [], 'app') => File::TYPE_FILE, $this->translator->trans('file.type.video', [], 'app') => File::TYPE_VIDEO];
            $builder->add('type', ChoiceType::class, ['choices' => $typeChoices, 'attr' => ['class' => 'form-select'], 'label' => 'form.entity.file.label.type']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'app',
            'attr' => ['class' => 'd-flex flex-column'],
            'mimeTypes' => null,
            'extensions' => null,
            'selectType' => false,
            'requiredFile' => true
        ]);
    }
}

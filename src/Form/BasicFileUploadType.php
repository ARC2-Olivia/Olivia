<?php

namespace App\Form;

use App\Entity\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
        if (true === $options['complexMode']) {
            $typeChoices = [$this->translator->trans('file.type.file', domain: 'app') => File::TYPE_FILE, $this->translator->trans('file.type.video', domain: 'app') => File::TYPE_VIDEO];
            $booleanChoices = [$this->translator->trans('common.no', domain: 'app') => false, $this->translator->trans('common.yes', domain: 'app') => true];
            $includeInChoices = [
                $this->translator->trans('form.entity.file.choices.includeIn.topicIndexDefault', domain: 'app') => File::INCLUDE_IN_TOPIC_INDEX_DEFAULT,
                $this->translator->trans('form.entity.file.choices.includeIn.topicIndexAlternate', domain: 'app') => File::INCLUDE_IN_TOPIC_INDEX_ALTERNATE
            ];
            $builder
                ->add('type', ChoiceType::class, ['label' => 'form.entity.file.label.type', 'choices' => $typeChoices, 'attr' => ['class' => 'form-select mb-3']])
                ->add('seminar', ChoiceType::class, ['label' => 'form.entity.file.label.seminar', 'choices' => $booleanChoices, 'attr' => ['class' => 'form-select mb-3']])
                ->add('includeIn', ChoiceType::class, ['label' => 'form.entity.file.label.includeIn', 'multiple' => true, 'choices' => $includeInChoices, 'attr' => ['class' => 'mb-3', 'data-df-select' => '']])
                ->add('displayText', TextareaType::class, ['label' => 'form.entity.file.label.displayText', 'required' => false, 'attr' => ['class' => 'form-textarea']])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'app',
            'attr' => ['class' => 'd-flex flex-column'],
            'mimeTypes' => null,
            'extensions' => null,
            'complexMode' => false,
            'requiredFile' => true
        ]);
    }
}

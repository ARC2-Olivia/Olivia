<?php

namespace App\Form;

use App\Entity\QuizQuestion;
use App\Entity\QuizQuestionChoice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuizQuestionChoiceType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('text', TextType::class, [
                'label' => 'form.entity.quizQuestionChoice.label.text',
                'attr' => ['class' => 'form-input mb-3']
            ])
            ->add('correct', ChoiceType::class, [
                'label' => 'form.entity.quizQuestionChoice.label.correct',
                'choices' => [
                    $this->translator->trans('common.falseValue', domain: 'app') => 0,
                    $this->translator->trans('common.trueValue', domain: 'app') => 1
                ],
                'attr' => ['class' => 'form-select mb-3']
            ])
        ;

        if (true === $options['include_translatable_fields']) {
            $builder->add('textAlt', TextType::class, [
                'mapped' => false,
                'label' => 'form.entity.quizQuestionChoice.label.textAlt',
                'attr' => ['class' => 'form-input mb-3']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuizQuestionChoice::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false
        ]);
    }
}

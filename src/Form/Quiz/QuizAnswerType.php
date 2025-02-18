<?php

namespace App\Form\Quiz;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuizAnswerType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $answerChoices = [
            $this->translator->trans('common.falseValue', [], 'app') => false,
            $this->translator->trans('common.trueValue', [], 'app') => true
        ];

        $builder->add('answer', ChoiceType::class, ['choices' => $answerChoices, 'attr' => ['class' => 'form-select mb-5']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['translation_domain' => 'app', 'attr' => ['novalidate' => 'novalidate']]);
    }}

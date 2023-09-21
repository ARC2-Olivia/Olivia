<?php

namespace App\Form;

use App\Entity\QuizQuestion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuizQuestionType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var QuizQuestion $quizQuestion */
        $quizQuestion = $builder->getData();
        $editMode = $options['edit_mode'];

        $builder
            ->add('text', TextareaType::class, ['label' => 'form.entity.quizQuestion.label.text', 'attr' => ['class' => 'form-textarea mb-3']])
            ->add('explanation', HiddenType::class, ['label' => 'form.entity.quizQuestion.label.explanation', 'attr' => ['class' => 'form-textarea mb-3']])
        ;

        if (false === $editMode) {
            $builder->add('type', ChoiceType::class, [
                'label' => 'form.entity.quizQuestion.label.type',
                'choices' => [
                    $this->translator->trans('quizQuestion.type.trueFalse', domain: 'app') => QuizQuestion::TYPE_TRUE_FALSE,
                    $this->translator->trans('quizQuestion.type.singleChoice', domain: 'app') => QuizQuestion::TYPE_SINGLE_CHOICE
                ],
                'attr' => ['class' => 'form-select mb-3']
            ]);
        } else if (true === $editMode && QuizQuestion::TYPE_TRUE_FALSE === $quizQuestion->getType()) {
            $builder->add('correctAnswer', ChoiceType::class, [
                'label' => 'form.entity.quizQuestion.label.correctAnswer',
                'choices' => [
                    $this->translator->trans('common.falseValue', [], 'app') => false,
                    $this->translator->trans('common.trueValue', [], 'app') => true
                ],
                'attr' => ['class' => 'form-select mb-3']
            ]);
        }

        if ($options['include_translatable_field']) {
            $builder
                ->add('textAlt', TextareaType::class, ['mapped' => false, 'label' => 'form.entity.quizQuestion.label.textAlt', 'attr' => ['class' => 'form-textarea mb-3']])
                ->add('explanationAlt', HiddenType::class, ['mapped' => false, 'label' => 'form.entity.quizQuestion.label.explanationAlt', 'attr' => ['class' => 'form-textarea mb-3']])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuizQuestion::class,
            'translation_domain' => 'app',
            'edit_mode' => false,
            'attr' => ['novalidate' => 'novalidate'],
            'include_translatable_field' => false
        ]);
    }
}

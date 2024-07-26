<?php

namespace App\Form\DataRequest;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteSpecificDataRequestType extends AbstractType
{
    public static function getDefaultData(): \stdClass
    {
        $defaultData = new \stdClass();
        $defaultData->gdprs = false;
        $defaultData->notes = false;
        $defaultData->enrollments = false;
        $defaultData->lessonCompletions = false;
        $defaultData->quizQuestionAnswers = false;
        $defaultData->practicalSubmoduleAssessments = false;
        $defaultData->other = '';
        return $defaultData;
    }

    private ?TranslatorInterface $translator = null;
    private ?RouterInterface $router = null;

    public function __construct(TranslatorInterface $translator, RouterInterface $router)
    {
        $this->translator = $translator;
        $this->router = $router;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $booleanChoices = [
            $this->translator->trans('common.no', [], 'app') => false,
            $this->translator->trans('common.yes', [], 'app') => true
        ];

        $builder
            ->add('gdprs', CheckboxType::class, ['label' => 'form.dataRequest.deleteSpecific.label.gdprs'])
            ->add('notes', CheckboxType::class, ['label' => 'form.dataRequest.deleteSpecific.label.notes'])
            ->add('enrollments', CheckboxType::class, ['label' => 'form.dataRequest.deleteSpecific.label.enrollments'])
            ->add('lessonCompletions', CheckboxType::class, ['label' => 'form.dataRequest.deleteSpecific.label.lessonCompletions'])
            ->add('quizQuestionAnswers', CheckboxType::class, ['label' => 'form.dataRequest.deleteSpecific.label.quizQuestionAnswers'])
            ->add('practicalSubmoduleAssessments', CheckboxType::class, ['label' => 'form.dataRequest.deleteSpecific.label.practicalSubmoduleAssessments'])
            ->add('other', TextareaType::class, ['label' => 'form.dataRequest.deleteSpecific.label.other', 'attr' => ['class' => 'form-textarea mb-3']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $confirmation = $this->translator->trans('profile.request.delete.confirmation', [], 'app');
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,
            'translation_domain' => 'app',
            'method' => 'post',
            'action' => $this->router->generate('gdpr_data_protection_delete_specific'),
            'attr' => [
                'class' => 'modal-dialog-content',
                'onsubmit' => "return confirm(`$confirmation`);"
            ]
        ]);
    }
}
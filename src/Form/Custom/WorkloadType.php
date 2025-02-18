<?php

namespace App\Form\Custom;

use App\Form\Transformer\WorkloadToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class WorkloadType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('workloadValue', TextType::class, [
            'attr' => ['class' => 'w-100 form-input', 'inputmode' => 'numeric', 'placeholder' => $this->translator->trans('form.entity.course.placeholder.workloadValue', [], 'app')],
            'constraints' => [
                new Assert\Regex([
                    'pattern' => '/^(\d*|\d+(\.\d*)?)$/',
                    'match' => true,
                    'message' => 'error.course.estimatedWorkload.value'
                ])
            ]
        ]);
        $builder->add('workloadTime', ChoiceType::class, [
            'choices' => $options['allowed_workload_times'],
            'attr' => ['class' => 'w-100 form-select'],
        ]);
        $builder->addViewTransformer(new WorkloadToStringTransformer());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('allowed_workload_times', []);
        $resolver->setAllowedTypes('allowed_workload_times', 'array');
    }
}
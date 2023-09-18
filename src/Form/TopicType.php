<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\PracticalSubmodule;
use App\Entity\Topic;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class TopicType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $editMode = $options['edit_mode'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'form.entity.topic.label.title',
                'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.topic.placeholder.title', domain: 'app')]
            ])
            ->add('theoreticalSubmodules', EntityType::class, [
                'required' => false,
                'class' => Course::class,
                'label' => 'form.entity.topic.label.theoreticalSubmodules',
                'placeholder' => 'form.entity.topic.placeholder.theoreticalSubmodules',
                'choice_label' => 'name',
                'multiple' => true,
                'query_builder' => function (EntityRepository $repo) use ($editMode) {
                    $qb = $repo->createQueryBuilder('ts');
                    if (false === $editMode) $qb->where('ts.topic IS NULL');
                    return $qb;
                },
                'attr' => ['class' => 'mb-3', 'data-df-select' => '']
            ])
            ->add('practicalSubmodules', EntityType::class, [
                'required' => false,
                'class' => PracticalSubmodule::class,
                'label' => 'form.entity.topic.label.practicalSubmodules',
                'placeholder' => 'form.entity.topic.placeholder.practicalSubmodules',
                'choice_label' => 'name',
                'multiple' => true,
                'query_builder' => function (EntityRepository $repo) use ($editMode) {
                    $qb = $repo->createQueryBuilder('ps');
                    if (false === $editMode) $qb->where('ps.topic IS NULL');
                    return $qb;
                },
                'attr' => ['class' => 'mb-3', 'data-df-select' => '']
            ])
        ;

        if (true === $options['include_translatable_fields']) {
            $builder
                ->add('titleAlt', TextType::class, [
                    'mapped' => false,
                    'label' => 'form.entity.topic.label.titleAlt',
                    'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.topic.placeholder.title', domain: 'app')]
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
            'edit_mode' => false,
            'attr' => [
                'class' => 'd-flex flex-column',
                'novalidate' => 'novalidate'
            ]
        ]);
    }
}

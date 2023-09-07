<?php

namespace App\Form;

use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorGroup;
use App\Repository\PracticalSubmoduleProcessorGroupRepository;
use App\Repository\PracticalSubmoduleProcessorRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PracticalSubmoduleProcessorGroupType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PracticalSubmoduleProcessorGroup $processorGroup */
        $processorGroup = $builder->getData();

        $builder
            ->add('processors', EntityType::class, [
                'mapped' => false,
                'label' => 'form.entity.practicalSubmoduleProcessorGroup.label.processors',
                'attr' => ['class' => 'mb-3', 'data-df-select' => ''],
                'class' => PracticalSubmoduleProcessor::class,
                'multiple' => true,
                'choice_label' => 'name',
                'query_builder' => $this->makeQueryBuilder($builder, $options['edit_mode']),
                'data' => $processorGroup->getPracticalSubmoduleProcessors()
            ])
            ->add('title', TextType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessorGroup.label.title',
                'attr' => ['class' => 'form-input mb-3']
            ])
        ;

        if ($options['include_translatable_fields']) {
            $builder->add('titleAlt', TextType::class, [
                'label' => 'form.entity.practicalSubmoduleProcessorGroup.label.titleAlt',
                'attr' => ['class' => 'form-input mb-3'],
                'mapped' => false
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmoduleProcessorGroup::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false,
            'edit_mode' => false,
            'attr' => ['class' => 'd-flex flex-column', 'novalidate' => 'novalidate']
        ]);
    }

    private function makeQueryBuilder(FormBuilderInterface $builder, bool $editMode): ?\Closure
    {
        /** @var PracticalSubmoduleProcessorGroup $processorGroup */
        $processorGroup = $builder->getData();
        $practicalSubmodule = $processorGroup?->getPracticalSubmodule();

        if (null === $practicalSubmodule) {
            return null;
        }

        return function (PracticalSubmoduleProcessorRepository $practicalSubmoduleProcessorRepository) use ($processorGroup, $editMode, $practicalSubmodule) {
            $qb = $practicalSubmoduleProcessorRepository->createQueryBuilder('psp')
                ->where('psp.practicalSubmodule = :submodule')
                ->setParameter('submodule', $practicalSubmodule)
                ->orderBy('psp.position', 'ASC');

            if (true === $editMode) {
                $qb->andWhere('psp.practicalSubmoduleProcessorGroup IS NULL OR psp.practicalSubmoduleProcessorGroup = :processorGroup')->setParameter('processorGroup', $processorGroup);
            } else {
                $qb->andWhere('psp.practicalSubmoduleProcessorGroup IS NULL');
            }

            return $qb;
        };
    }
}

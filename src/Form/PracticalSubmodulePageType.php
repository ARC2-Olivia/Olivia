<?php

namespace App\Form;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmodulePage;
use App\Entity\PracticalSubmoduleQuestion;
use App\Repository\PracticalSubmoduleQuestionRepository;
use App\Validator\PageAndQuestions;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PracticalSubmodulePageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PracticalSubmodulePage $page */
        $page = $builder->getData();

        $builder
            ->add('questions', EntityType::class, [
                'mapped' => false,
                'label' => 'form.entity.practicalSubmodulePage.label.questions',
                'attr' => ['class' => 'form-select multiple mb-3'],
                'class' => PracticalSubmoduleQuestion::class,
                'multiple' => true,
                'choice_label' => 'questionText',
                'query_builder' => $this->makeQueryBuilder($builder, $options['edit_mode']),
                'data' => $page->getPracticalSubmoduleQuestions(),
                'constraints' => [
                    new PageAndQuestions(['submoduleToMatch' => $page->getPracticalSubmodule()])
                ]
            ])
            ->add('title', TextType::class, [
                'label' => 'form.entity.practicalSubmodulePage.label.title',
                'attr' => ['class' => 'form-input mb-3'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.entity.practicalSubmodulePage.label.description',
                'attr' => ['class' => 'form-textarea mb-3']
            ]);

        if ($options['include_translatable_fields']) {
            $builder
                ->add('titleAlt', TextType::class, [
                    'label' => 'form.entity.practicalSubmodulePage.label.titleAlt',
                    'attr' => ['class' => 'form-input mb-3'],
                    'mapped' => false
                ])
                ->add('descriptionAlt', TextareaType::class, [
                    'label' => 'form.entity.practicalSubmodulePage.label.descriptionAlt',
                    'attr' => ['class' => 'form-textarea mb-3'],
                    'mapped' => false
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmodulePage::class,
            'translation_domain' => 'app',
            'include_translatable_fields' => false,
            'edit_mode' => false,
            'attr' => ['class' => 'd-flex flex-column', 'novalidate' => 'novalidate']
        ]);
    }

    private function makeQueryBuilder(FormBuilderInterface $builder, bool $editMode): \Closure
    {
        /** @var PracticalSubmodulePage $page */
        $page = $builder->getData();
        $practicalSubmodule = $page?->getPracticalSubmodule();

        $queryBuilder = null;
        if ($practicalSubmodule !== null) {
            $queryBuilder = function (PracticalSubmoduleQuestionRepository $practicalSubmoduleQuestionRepository) use ($page, $editMode, $practicalSubmodule) {
                $qb = $practicalSubmoduleQuestionRepository->createQueryBuilder('psq')
                    ->where('psq.practicalSubmodule = :submodule')
                    ->setParameter('submodule', $practicalSubmodule)
                    ->orderBy('psq.position', 'ASC');

                if ($editMode === true) {
                    $qb->andWhere('psq.practicalSubmodulePage IS NULL OR psq.practicalSubmodulePage = :page')->setParameter('page', $page);
                } else {
                    $qb->andWhere('psq.practicalSubmodulePage IS NULL');
                }

                return $qb;
            };
        }
        return $queryBuilder;
    }
}

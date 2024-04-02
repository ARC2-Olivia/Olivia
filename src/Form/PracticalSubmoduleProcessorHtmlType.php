<?php

namespace App\Form;

use App\Entity\File;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorHtml;
use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalSubmoduleProcessorHtmlType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var PracticalSubmoduleProcessor $practicalSubmoduleProcessor */
        $practicalSubmoduleProcessor = $builder->getData()?->getPracticalSubmoduleProcessor();

        $builder
            ->add('practicalSubmoduleQuestion', EntityType::class, [
                'class' => PracticalSubmoduleQuestion::class,
                'label' => 'form.entity.practicalSubmoduleProcessor.label.evaluationQuestion',
                'choice_label' => 'questionText',
                'query_builder' => $this->makeQueryBuilder($builder),
                'attr' => ['class' => 'mb-3', 'data-df-select' => ''],
                'placeholder' => 'form.entity.practicalSubmoduleProcessor.placeholder.evaluationQuestion'
            ])
            ->add('expectedValue', TextType::class, ['label' => 'form.entity.practicalSubmoduleProcessor.label.expectedValue', 'attr' => ['class' => 'form-input mb-3']])
            ->add('resultText', HiddenType::class, ['label' => 'form.entity.practicalSubmoduleProcessor.label.resultText'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmoduleProcessorHtml::class,
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate', 'class' => 'd-flex flex-column']
        ]);
    }

    private function makeQueryBuilder(FormBuilderInterface $builder): ?\Closure
    {
        /** @var PracticalSubmoduleProcessor $practicalSubmoduleProcessor */
        $practicalSubmoduleProcessor = $builder->getData()?->getPracticalSubmoduleProcessor();
        $queryBuilder = null;
        if ($practicalSubmoduleProcessor !== null) {
            $queryBuilder = function (EntityRepository $repository) use ($practicalSubmoduleProcessor) {
                return $repository->createQueryBuilder('psq')
                    ->where('psq.practicalSubmodule = :submodule')
                    ->andWhere('psq.evaluable = :evaluable')
                    ->andWhere('psq.type NOT IN (:types)')
                    ->setParameters([
                        'submodule' => $practicalSubmoduleProcessor->getPracticalSubmodule(),
                        'evaluable' => true,
                        'types' => [PracticalSubmoduleQuestion::TYPE_TEMPLATED_TEXT_INPUT, PracticalSubmoduleQuestion::TYPE_STATIC_TEXT]
                    ]);
            };
        }
        return $queryBuilder;
    }
}

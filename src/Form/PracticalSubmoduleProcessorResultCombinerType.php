<?php

namespace App\Form;

use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorResultCombiner;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalSubmoduleProcessorResultCombinerType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('practicalSubmoduleProcessors', EntityType::class, [
            'class' => PracticalSubmoduleProcessor::class,
            'label' => 'form.entity.practicalSubmoduleProcessor.label.evaluationEvaluators',
            'choice_label' => 'name',
            'query_builder' => $this->makePracticalSubmoduleProcessorQueryBuilder($builder),
            'attr' => ['class' => 'mb-3', 'data-df-select' => ''],
            'multiple' => true
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PracticalSubmoduleProcessorResultCombiner::class,
            'translation_domain' => 'app',
            'attr' => [
                'novalidate' => 'novalidate',
                'class' => 'd-flex flex-column'
            ]
        ]);
    }

    private function makePracticalSubmoduleProcessorQueryBuilder(FormBuilderInterface $builder)
    {
        /** @var PracticalSubmoduleProcessor $practicalSubmoduleProcessor */
        $practicalSubmoduleProcessor = $builder->getData()?->getPracticalSubmoduleProcessor();
        $queryBuilder = null;
        if ($practicalSubmoduleProcessor !== null) {
            $queryBuilder = function (EntityRepository $repository) use ($practicalSubmoduleProcessor) {
                return $repository->createQueryBuilder('psp')
                    ->where('psp != :processor')
                    ->andWhere('psp.practicalSubmodule = :submodule')
                    ->andWhere('psp.included = :included')
                    ->setParameters(['processor' => $practicalSubmoduleProcessor, 'submodule' => $practicalSubmoduleProcessor->getPracticalSubmodule(), 'included' => false]);
            };
        }
        return $queryBuilder;
    }
}

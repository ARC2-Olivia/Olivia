<?php

namespace App\Service;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorHtml;
use App\Entity\PracticalSubmoduleProcessorImplementationInterface;
use App\Entity\PracticalSubmoduleProcessorProductAggregate;
use App\Entity\PracticalSubmoduleProcessorSimple;
use App\Entity\PracticalSubmoduleProcessorSumAggregate;
use App\Entity\PracticalSubmoduleProcessorTemplatedText;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use App\Entity\User;
use App\Exception\InvalidPracticalSubmoduleQuestionTypeException;
use App\Exception\PSImport\ErroneousFirstTaskException;
use App\Exception\PSImport\MissingTaskOrderKeyException;
use App\Exception\PSImport\WrongFirstTaskTypeException;
use App\Exception\UnsupportedEvaluationEvaluatorTypeException;
use App\Form\PracticalSubmoduleProcessorHtmlType;
use App\Form\PracticalSubmoduleProcessorProductAggregateType;
use App\Form\PracticalSubmoduleProcessorSimpleType;
use App\Form\PracticalSubmoduleProcessorSumAggregateType;
use App\Form\PracticalSubmoduleProcessorTemplatedTextType;
use App\Misc\ProcessorResult;
use App\Misc\PSExporter;
use App\Misc\PSImporter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PracticalSubmoduleService
{
    private ?EntityManagerInterface $em = null;
    private ?ValidatorInterface $validator = null;
    private ?ParameterBagInterface $parameterBag = null;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator, ParameterBagInterface $parameterBag)
    {
        $this->em = $em;
        $this->validator = $validator;
        $this->parameterBag = $parameterBag;
    }

    public function export(PracticalSubmodule $practicalSubmodule): array
    {
        return (new PSExporter($practicalSubmodule, $this->em, $this->parameterBag->get('locale.alternate')))->export();
    }

    /**
     * @throws ErroneousFirstTaskException
     * @throws WrongFirstTaskTypeException
     * @throws MissingTaskOrderKeyException
     */
    public function import(array $tasks): ?PracticalSubmodule
    {
        return (new PSImporter($tasks, $this->em, $this->parameterBag->get('locale.alternate')))->import();
    }

    /**
     * @throws InvalidPracticalSubmoduleQuestionTypeException
     */
    public function getNextAnswerValueForMultiChoiceQuestion(PracticalSubmoduleQuestion $practicalSubmoduleQuestion): int
    {
        if ($practicalSubmoduleQuestion->getType() !== PracticalSubmoduleQuestion::TYPE_MULTI_CHOICE) {
            throw InvalidPracticalSubmoduleQuestionTypeException::forMultiChoiceType($practicalSubmoduleQuestion->getType());
        }

        $maxValue = $this->em->getRepository(PracticalSubmoduleQuestionAnswer::class)->getMaxAnswerValueForQuestion($practicalSubmoduleQuestion);
        $nextValue = $maxValue + 1;
        return $nextValue;
    }

    public function resetAnswerValuesForMultiChoiceQuestion(PracticalSubmoduleQuestion $practicalSubmoduleQuestion): void
    {
        if ($practicalSubmoduleQuestion->getType() !== PracticalSubmoduleQuestion::TYPE_MULTI_CHOICE) {
            throw InvalidPracticalSubmoduleQuestionTypeException::forMultiChoiceType($practicalSubmoduleQuestion->getType());
        }

        $questionAnswers = $this->em->getRepository(PracticalSubmoduleQuestionAnswer::class)->findBy(['practicalSubmoduleQuestion' => $practicalSubmoduleQuestion],  ['id' => 'ASC']);
        $answerValue = 1;
        foreach ($questionAnswers as $questionAnswer) {
            $questionAnswer->setAnswerValue($answerValue++);
        }
        $this->em->flush();
    }

    /**
     * @throws UnsupportedEvaluationEvaluatorTypeException
     */
    public function getProcessorImplementation(PracticalSubmoduleProcessor $processor): PracticalSubmoduleProcessorImplementationInterface
    {
        if ($processor->getType() === PracticalSubmoduleProcessor::TYPE_SIMPLE) {
            $processorImpl = $this->em->getRepository(PracticalSubmoduleProcessorSimple::class)->findOneBy(['practicalSubmoduleProcessor' => $processor]);
            if ($processorImpl === null) $processorImpl = (new PracticalSubmoduleProcessorSimple())->setPracticalSubmoduleProcessor($processor);
            return $processorImpl;
        }

        if ($processor->getType() === PracticalSubmoduleProcessor::TYPE_HTML) {
            $processorImpl = $this->em->getRepository(PracticalSubmoduleProcessorHtml::class)->findOneBy(['practicalSubmoduleProcessor' => $processor]);
            if ($processorImpl === null) $processorImpl = (new PracticalSubmoduleProcessorHtml())->setPracticalSubmoduleProcessor($processor);
            return $processorImpl;
        }

        if ($processor->getType() === PracticalSubmoduleProcessor::TYPE_SUM_AGGREGATE) {
            $processorImpl = $this->em->getRepository(PracticalSubmoduleProcessorSumAggregate::class)->findOneBy(['practicalSubmoduleProcessor' => $processor]);
            if ($processorImpl === null) $processorImpl = (new PracticalSubmoduleProcessorSumAggregate())->setPracticalSubmoduleProcessor($processor);
            return $processorImpl;
        }

        if ($processor->getType() === PracticalSubmoduleProcessor::TYPE_PRODUCT_AGGREGATE) {
            $processorImpl = $this->em->getRepository(PracticalSubmoduleProcessorProductAggregate::class)->findOneBy(['practicalSubmoduleProcessor' => $processor]);
            if ($processorImpl === null) $processorImpl = (new PracticalSubmoduleProcessorProductAggregate())->setPracticalSubmoduleProcessor($processor);
            return $processorImpl;
        }

        if ($processor->getType() === PracticalSubmoduleProcessor::TYPE_TEMPLATED_TEXT) {
            $processorImpl = $this->em->getRepository(PracticalSubmoduleProcessorTemplatedText::class)->findOneBy(['practicalSubmoduleProcessor' => $processor]);
            if ($processorImpl === null) $processorImpl = (new PracticalSubmoduleProcessorTemplatedText())->setPracticalSubmoduleProcessor($processor);
            return $processorImpl;
        }

        throw UnsupportedEvaluationEvaluatorTypeException::withDefaultMessage();
    }

    /**
     * @throws UnsupportedEvaluationEvaluatorTypeException
     */
    public function getProcessorImplementationFormClass(PracticalSubmoduleProcessor $processor): string
    {
        $formClass = match ($processor->getType()) {
            PracticalSubmoduleProcessor::TYPE_SIMPLE => PracticalSubmoduleProcessorSimpleType::class,
            PracticalSubmoduleProcessor::TYPE_HTML => PracticalSubmoduleProcessorHtmlType::class,
            PracticalSubmoduleProcessor::TYPE_SUM_AGGREGATE => PracticalSubmoduleProcessorSumAggregateType::class,
            PracticalSubmoduleProcessor::TYPE_PRODUCT_AGGREGATE => PracticalSubmoduleProcessorProductAggregateType::class,
            PracticalSubmoduleProcessor::TYPE_TEMPLATED_TEXT => PracticalSubmoduleProcessorTemplatedTextType::class,
            default => null
        };
        if ($formClass === null) throw UnsupportedEvaluationEvaluatorTypeException::withDefaultMessage();
        return $formClass;
    }

    public function prepareAssessment(PracticalSubmodule $practicalSubmodule, User $user): PracticalSubmoduleAssessment
    {
        $assessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $user]);
        $created = false;
        if ($assessment === null) {
            $assessment = (new PracticalSubmoduleAssessment())->setPracticalSubmodule($practicalSubmodule)->setUser($user)->setTakenAt(new \DateTimeImmutable())->setCompleted(false);
            $this->em->persist($assessment);
            $this->em->flush();
            $created = true;
        }
        if (!$created) {
            $assessment->setTakenAt(new \DateTimeImmutable())->setCompleted(false);
            $this->em->flush();
        }
        return $assessment;
    }

    /** @return ProcessorResult[] */
    public function runProcessors(PracticalSubmoduleAssessment $assessment): array
    {
        $results = [];

        $processors = $this->em->getRepository(PracticalSubmoduleProcessor::class)->findBy(['practicalSubmodule' => $assessment->getPracticalSubmodule(), 'included' => true], ['position' => 'ASC']);
        foreach ($processors as $processor) {
            $result = match ($processor->getType()) {
                PracticalSubmoduleProcessor::TYPE_SIMPLE => $this->runSimpleProcessor($processor, $assessment),
                PracticalSubmoduleProcessor::TYPE_HTML => $this->runHtmlProcessor($processor, $assessment),
                PracticalSubmoduleProcessor::TYPE_SUM_AGGREGATE => $this->runSumAggregateProcessor($processor, $assessment),
                PracticalSubmoduleProcessor::TYPE_PRODUCT_AGGREGATE => $this->runProductAggregateProcessor($processor, $assessment),
                PracticalSubmoduleProcessor::TYPE_TEMPLATED_TEXT => $this->runTemplatedTextProcessor($processor, $assessment),
                default => null
            };

            if ($result !== null) $results[] = $result;
        }

        return $results;
    }

    public function runSimpleProcessor(PracticalSubmoduleProcessor $processor, PracticalSubmoduleAssessment $assessment): ?ProcessorResult
    {
        $processorSimple = $processor->getPracticalSubmoduleProcessorSimple();
        $errors = $this->validator->validate($processorSimple);
        if ($errors->count() > 0 || $processorSimple->getPracticalSubmoduleQuestion() === null || !$processorSimple->checkConformity($assessment)) return null;
        return new ProcessorResult($processorSimple->getResultText(), $processorSimple->getPracticalSubmoduleProcessor()->getResultFiles()->toArray());
    }

    private function runHtmlProcessor(PracticalSubmoduleProcessor $processor, PracticalSubmoduleAssessment $assessment): ?ProcessorResult
    {
        $processorHtml = $processor->getPracticalSubmoduleProcessorHtml();
        $errors = $this->validator->validate($processorHtml);
        if ($errors->count() > 0 || $processorHtml->getPracticalSubmoduleQuestion() === null || !$processorHtml->checkConformity($assessment)) return null;
        return new ProcessorResult($processorHtml->getResultText(), $processorHtml->getPracticalSubmoduleProcessor()->getResultFiles()->toArray());
    }

    private function runSumAggregateProcessor(PracticalSubmoduleProcessor $processor, PracticalSubmoduleAssessment $assessment): ?ProcessorResult
    {
        $processorSumAggregate = $processor->getPracticalSubmoduleProcessorSumAggregate();
        $errors = $this->validator->validate($processorSumAggregate);
        if ($errors->count() > 0 || !$processorSumAggregate->checkConformity($assessment, $this->validator)) return null;
        return new ProcessorResult($processorSumAggregate->getResultText(), $processorSumAggregate->getPracticalSubmoduleProcessor()->getResultFiles()->toArray());
    }

    private function runProductAggregateProcessor(PracticalSubmoduleProcessor $processor, PracticalSubmoduleAssessment $assessment): ?ProcessorResult
    {
        $processorProductAggregate = $processor->getPracticalSubmoduleProcessorProductAggregate();
        $errors = $this->validator->validate($processorProductAggregate);
        if ($errors->count() > 0 || !$processorProductAggregate->checkConformity($assessment, $this->validator)) return null;
        return new ProcessorResult($processorProductAggregate->getResultText(), $processorProductAggregate->getPracticalSubmoduleProcessor()->getResultFiles()->toArray());
    }

    private function runTemplatedTextProcessor(PracticalSubmoduleProcessor $processor, PracticalSubmoduleAssessment $assessment): ?ProcessorResult
    {
        $processorTemplatedText = $processor->getPracticalSubmoduleProcessorTemplatedText();
        $errors = $this->validator->validate($processorTemplatedText);
        if ($errors->count() > 0 || !$processorTemplatedText->checkConformity($assessment)) return null;
        $processorTemplatedText->calculateResult($assessment);
        return new ProcessorResult($processorTemplatedText->getResultText(), $processorTemplatedText->getPracticalSubmoduleProcessor()->getResultFiles()->toArray());
    }
}
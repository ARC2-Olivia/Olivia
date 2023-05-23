<?php

namespace App\Service;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorImplementationInterface;
use App\Entity\PracticalSubmoduleProcessorProductAggregate;
use App\Entity\PracticalSubmoduleProcessorSimple;
use App\Entity\PracticalSubmoduleProcessorSumAggregate;
use App\Entity\PracticalSubmoduleProcessorTemplatedText;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\User;
use App\Exception\UnsupportedEvaluationEvaluatorTypeException;
use App\Form\PracticalSubmoduleProcessorProductAggregateType;
use App\Form\PracticalSubmoduleProcessorSimpleType;
use App\Form\PracticalSubmoduleProcessorSumAggregateType;
use App\Form\PracticalSubmoduleProcessorTemplatedTextType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PracticalSubmoduleService
{
    private ?EntityManagerInterface $em = null;
    private ?ValidatorInterface $validator = null;

    public function __construct(EntityManagerInterface $em, ValidatorInterface $validator)
    {
        $this->em = $em;
        $this->validator = $validator;
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

    /** @return string[] */
    public function runProcessors(PracticalSubmoduleAssessment $assessment): array
    {
        $messages = [];

        $evaluators = $this->em->getRepository(PracticalSubmoduleProcessor::class)->findBy(['practicalSubmodule' => $assessment->getPracticalSubmodule(), 'included' => true]);
        foreach ($evaluators as $evaluator) {
            $message = match ($evaluator->getType()) {
                PracticalSubmoduleProcessor::TYPE_SIMPLE => $this->runSimpleProcessor($evaluator, $assessment),
                PracticalSubmoduleProcessor::TYPE_SUM_AGGREGATE => $this->runSumAggregateProcessor($evaluator, $assessment),
                PracticalSubmoduleProcessor::TYPE_PRODUCT_AGGREGATE => $this->runProductAggregateProcessor($evaluator, $assessment),
                PracticalSubmoduleProcessor::TYPE_TEMPLATED_TEXT => $this->runTemplatedTextProcessor($evaluator, $assessment),
                default => null
            };

            if ($message !== null) $messages[] = $message;
        }

        return $messages;
    }

    public function runSimpleProcessor(PracticalSubmoduleProcessor $processor, PracticalSubmoduleAssessment $assessment): ?string
    {
        $processorSimple = $processor->getPracticalSubmoduleProcessorSimple();
        $errors = $this->validator->validate($processorSimple);
        if ($errors->count() > 0 || $processorSimple->getPracticalSubmoduleQuestion() === null || !$processorSimple->checkConformity($assessment)) return null;
        return $processorSimple->getResultText();
    }

    private function runSumAggregateProcessor(PracticalSubmoduleProcessor $processor, PracticalSubmoduleAssessment $assessment): ?string
    {
        $processorSumAggregate = $processor->getPracticalSubmoduleProcessorSumAggregate();
        $errors = $this->validator->validate($processorSumAggregate);
        if ($errors->count() > 0 || !$processorSumAggregate->checkConformity($assessment, $this->validator)) return null;
        return $processorSumAggregate->getResultText();
    }

    private function runProductAggregateProcessor(PracticalSubmoduleProcessor $processor, PracticalSubmoduleAssessment $assessment): ?string
    {
        $processorProductAggregate = $processor->getPracticalSubmoduleProcessorProductAggregate();
        $errors = $this->validator->validate($processorProductAggregate);
        if ($errors->count() > 0 || !$processorProductAggregate->checkConformity($assessment, $this->validator)) return null;
        return $processorProductAggregate->getResultText();
    }

    private function runTemplatedTextProcessor(PracticalSubmoduleProcessor $processor, PracticalSubmoduleAssessment $assessment): ?string
    {
        $processorTemplatedText = $processor->getPracticalSubmoduleProcessorTemplatedText();
        $errors = $this->validator->validate($processorTemplatedText);
        if ($errors->count() > 0) return null;
        $processorTemplatedText->calculateResult($assessment);
        return $processorTemplatedText->getResultText();
    }
}
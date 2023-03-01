<?php

namespace App\Service;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\PracticalSubmoduleProcessor;
use App\Entity\PracticalSubmoduleProcessorProductAggregate;
use App\Entity\PracticalSubmoduleProcessorSimple;
use App\Entity\PracticalSubmoduleProcessorSumAggregate;
use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\User;
use App\Exception\UnsupportedEvaluationEvaluatorTypeException;
use App\Form\PracticalSubmoduleProcessorProductAggregateType;
use App\Form\PracticalSubmoduleProcessorSimpleType;
use App\Form\PracticalSubmoduleProcessorSumAggregateType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EvaluationService
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
    public function getEvaluatorImplementation(PracticalSubmoduleProcessor $evaluationEvaluator): PracticalSubmoduleProcessorSimple|PracticalSubmoduleProcessorSumAggregate|PracticalSubmoduleProcessorProductAggregate
    {
        if ($evaluationEvaluator->getType() === PracticalSubmoduleProcessor::TYPE_SIMPLE) {
            $evaluatorImpl = $this->em->getRepository(PracticalSubmoduleProcessorSimple::class)->findOneBy(['evaluationEvaluator' => $evaluationEvaluator]);
            if ($evaluatorImpl === null) $evaluatorImpl = (new PracticalSubmoduleProcessorSimple())->setPracticalSubmoduleProcessor($evaluationEvaluator);
            return $evaluatorImpl;
        }

        if ($evaluationEvaluator->getType() === PracticalSubmoduleProcessor::TYPE_SUM_AGGREGATE) {
            $evaluatorImpl = $this->em->getRepository(PracticalSubmoduleProcessorSumAggregate::class)->findOneBy(['evaluationEvaluator' => $evaluationEvaluator]);
            if ($evaluatorImpl === null) $evaluatorImpl = (new PracticalSubmoduleProcessorSumAggregate())->setPracticalSubmoduleProcessor($evaluationEvaluator);
            return $evaluatorImpl;
        }

        if ($evaluationEvaluator->getType() === PracticalSubmoduleProcessor::TYPE_PRODUCT_AGGREGATE) {
            $evaluatorImpl = $this->em->getRepository(PracticalSubmoduleProcessorProductAggregate::class)->findOneBy(['evaluationEvaluator' => $evaluationEvaluator]);
            if ($evaluatorImpl === null) $evaluatorImpl = (new PracticalSubmoduleProcessorProductAggregate())->setPracticalSubmoduleProcessor($evaluationEvaluator);
            return $evaluatorImpl;
        }

        throw UnsupportedEvaluationEvaluatorTypeException::withDefaultMessage();
    }

    /**
     * @throws UnsupportedEvaluationEvaluatorTypeException
     */
    public function getEvaluatorImplementationFormClass(PracticalSubmoduleProcessor $evaluationEvaluator): string
    {
        $formClass = match ($evaluationEvaluator->getType()) {
            PracticalSubmoduleProcessor::TYPE_SIMPLE => PracticalSubmoduleProcessorSimpleType::class,
            PracticalSubmoduleProcessor::TYPE_SUM_AGGREGATE => PracticalSubmoduleProcessorSumAggregateType::class,
            PracticalSubmoduleProcessor::TYPE_PRODUCT_AGGREGATE => PracticalSubmoduleProcessorProductAggregateType::class,
            default => null
        };

        if ($formClass === null) throw UnsupportedEvaluationEvaluatorTypeException::withDefaultMessage();
        return $formClass;
    }

    public function prepareEvaluationAssessment(PracticalSubmodule $evaluation, User $user): PracticalSubmoduleAssessment
    {
        $evaluationAssessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneBy(['evaluation' => $evaluation, 'user' => $user]);
        $created = false;
        if ($evaluationAssessment === null) {
            $evaluationAssessment = (new PracticalSubmoduleAssessment())->setPracticalSubmodule($evaluation)->setUser($user)->setTakenAt(new \DateTimeImmutable())->setCompleted(false);
            $this->em->persist($evaluationAssessment);
            $this->em->flush();
            $created = true;
        }
        if (!$created) {
            $evaluationAssessment->setTakenAt(new \DateTimeImmutable())->setCompleted(false);
            $this->em->flush();
        }
        return $evaluationAssessment;
    }

    /** @return string[] */
    public function runEvaluators(PracticalSubmoduleAssessment $evaluationAssessment): array
    {
        $messages = [];

        $evaluators = $this->em->getRepository(PracticalSubmoduleProcessor::class)->findBy(['evaluation' => $evaluationAssessment->getPracticalSubmodule(), 'included' => true]);
        foreach ($evaluators as $evaluator) {
            $message = match ($evaluator->getType()) {
                PracticalSubmoduleProcessor::TYPE_SIMPLE => $this->runSimpleEvaluator($evaluator, $evaluationAssessment),
                PracticalSubmoduleProcessor::TYPE_SUM_AGGREGATE => $this->runSumAggregateEvaluator($evaluator, $evaluationAssessment),
                PracticalSubmoduleProcessor::TYPE_PRODUCT_AGGREGATE => $this->runProductAggregateEvaluator($evaluator, $evaluationAssessment),
                default => null
            };

            if ($message !== null) $messages[] = $message;
        }

        return $messages;
    }

    public function runSimpleEvaluator(PracticalSubmoduleProcessor $evaluator, PracticalSubmoduleAssessment $evaluationAssessment): ?string
    {
        $evaluatorSimple = $evaluator->getPracticalSubmoduleProcessorSimple();
        $errors = $this->validator->validate($evaluatorSimple);
        if ($errors->count() > 0 || $evaluatorSimple->getPracticalSubmoduleQuestion() === null || !$evaluatorSimple->checkConformity($evaluationAssessment)) return null;
        return $evaluatorSimple->getResultText();
    }

    private function runSumAggregateEvaluator(PracticalSubmoduleProcessor $evaluator, PracticalSubmoduleAssessment $evaluationAssessment): ?string
    {
        $evaluatorSumAggregate = $evaluator->getPracticalSubmoduleProcessorSumAggregate();
        $errors = $this->validator->validate($evaluatorSumAggregate);
        if ($errors->count() > 0 || !$evaluatorSumAggregate->checkConformity($evaluationAssessment, $this->validator)) return null;
        return $evaluatorSumAggregate->getResultText();
    }

    private function runProductAggregateEvaluator(PracticalSubmoduleProcessor $evaluator, PracticalSubmoduleAssessment $evaluationAssessment): ?string
    {
        $evaluatorProductAggregate = $evaluator->getPracticalSubmoduleProcessorProductAggregate();
        $errors = $this->validator->validate($evaluatorProductAggregate);
        if ($errors->count() > 0 || !$evaluatorProductAggregate->checkConformity($evaluationAssessment, $this->validator)) return null;
        return $evaluatorProductAggregate->getResultText();
    }
}
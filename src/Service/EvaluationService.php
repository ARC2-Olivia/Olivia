<?php

namespace App\Service;

use App\Entity\Evaluation;
use App\Entity\EvaluationAssessment;
use App\Entity\EvaluationEvaluator;
use App\Entity\EvaluationEvaluatorProductAggregate;
use App\Entity\EvaluationEvaluatorSimple;
use App\Entity\EvaluationEvaluatorSumAggregate;
use App\Entity\EvaluationQuestion;
use App\Entity\User;
use App\Exception\UnsupportedEvaluationEvaluatorTypeException;
use App\Form\EvaluationEvaluatorProductAggregateType;
use App\Form\EvaluationEvaluatorSimpleType;
use App\Form\EvaluationEvaluatorSumAggregateType;
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
    public function getEvaluatorImplementation(EvaluationEvaluator $evaluationEvaluator): EvaluationEvaluatorSimple|EvaluationEvaluatorSumAggregate|EvaluationEvaluatorProductAggregate
    {
        if ($evaluationEvaluator->getType() === EvaluationEvaluator::TYPE_SIMPLE) {
            $evaluatorImpl = $this->em->getRepository(EvaluationEvaluatorSimple::class)->findOneBy(['evaluationEvaluator' => $evaluationEvaluator]);
            if ($evaluatorImpl === null) $evaluatorImpl = (new EvaluationEvaluatorSimple())->setEvaluationEvaluator($evaluationEvaluator);
            return $evaluatorImpl;
        }

        if ($evaluationEvaluator->getType() === EvaluationEvaluator::TYPE_SUM_AGGREGATE) {
            $evaluatorImpl = $this->em->getRepository(EvaluationEvaluatorSumAggregate::class)->findOneBy(['evaluationEvaluator' => $evaluationEvaluator]);
            if ($evaluatorImpl === null) $evaluatorImpl = (new EvaluationEvaluatorSumAggregate())->setEvaluationEvaluator($evaluationEvaluator);
            return $evaluatorImpl;
        }

        if ($evaluationEvaluator->getType() === EvaluationEvaluator::TYPE_PRODUCT_AGGREGATE) {
            $evaluatorImpl = $this->em->getRepository(EvaluationEvaluatorProductAggregate::class)->findOneBy(['evaluationEvaluator' => $evaluationEvaluator]);
            if ($evaluatorImpl === null) $evaluatorImpl = (new EvaluationEvaluatorProductAggregate())->setEvaluationEvaluator($evaluationEvaluator);
            return $evaluatorImpl;
        }

        throw UnsupportedEvaluationEvaluatorTypeException::withDefaultMessage();
    }

    /**
     * @throws UnsupportedEvaluationEvaluatorTypeException
     */
    public function getEvaluatorImplementationFormClass(EvaluationEvaluator $evaluationEvaluator): string
    {
        $formClass = match ($evaluationEvaluator->getType()) {
            EvaluationEvaluator::TYPE_SIMPLE => EvaluationEvaluatorSimpleType::class,
            EvaluationEvaluator::TYPE_SUM_AGGREGATE => EvaluationEvaluatorSumAggregateType::class,
            EvaluationEvaluator::TYPE_PRODUCT_AGGREGATE => EvaluationEvaluatorProductAggregateType::class,
            default => null
        };

        if ($formClass === null) throw UnsupportedEvaluationEvaluatorTypeException::withDefaultMessage();
        return $formClass;
    }

    public function prepareEvaluationAssessment(Evaluation $evaluation, User $user): EvaluationAssessment
    {
        $evaluationAssessment = $this->em->getRepository(EvaluationAssessment::class)->findOneBy(['evaluation' => $evaluation, 'user' => $user]);
        $created = false;
        if ($evaluationAssessment === null) {
            $evaluationAssessment = (new EvaluationAssessment())->setEvaluation($evaluation)->setUser($user)->setTakenAt(new \DateTimeImmutable())->setCompleted(false);
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
    public function runEvaluators(EvaluationAssessment $evaluationAssessment): array
    {
        $messages = [];

        $evaluators = $this->em->getRepository(EvaluationEvaluator::class)->findBy(['evaluation' => $evaluationAssessment->getEvaluation(), 'included' => true]);
        foreach ($evaluators as $evaluator) {
            $message = match ($evaluator->getType()) {
                EvaluationEvaluator::TYPE_SIMPLE => $this->runSimpleEvaluator($evaluator, $evaluationAssessment),
                EvaluationEvaluator::TYPE_SUM_AGGREGATE => $this->runSumAggregateEvaluator($evaluator, $evaluationAssessment),
                EvaluationEvaluator::TYPE_PRODUCT_AGGREGATE => $this->runProductAggregateEvaluator($evaluator, $evaluationAssessment),
                default => null
            };

            if ($message !== null) $messages[] = $message;
        }

        return $messages;
    }

    public function runSimpleEvaluator(EvaluationEvaluator $evaluator, EvaluationAssessment $evaluationAssessment): ?string
    {
        $evaluatorSimple = $evaluator->getEvaluationEvaluatorSimple();
        $errors = $this->validator->validate($evaluatorSimple);
        if ($errors->count() > 0 || $evaluatorSimple->getEvaluationQuestion() === null || !$evaluatorSimple->checkConformity($evaluationAssessment)) return null;
        return $evaluatorSimple->getResultText();
    }

    private function runSumAggregateEvaluator(EvaluationEvaluator $evaluator, EvaluationAssessment $evaluationAssessment): ?string
    {
        $evaluatorSumAggregate = $evaluator->getEvaluationEvaluatorSumAggregate();
        $errors = $this->validator->validate($evaluatorSumAggregate);
        if ($errors->count() > 0 || !$evaluatorSumAggregate->checkConformity($evaluationAssessment, $this->validator)) return null;
        return $evaluatorSumAggregate->getResultText();
    }

    private function runProductAggregateEvaluator(EvaluationEvaluator $evaluator, EvaluationAssessment $evaluationAssessment): ?string
    {
        $evaluatorProductAggregate = $evaluator->getEvaluationEvaluatorProductAggregate();
        $errors = $this->validator->validate($evaluatorProductAggregate);
        if ($errors->count() > 0 || !$evaluatorProductAggregate->checkConformity($evaluationAssessment, $this->validator)) return null;
        return $evaluatorProductAggregate->getResultText();
    }
}
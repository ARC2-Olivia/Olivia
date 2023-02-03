<?php

namespace App\Service;

use App\Entity\Evaluation;
use App\Entity\EvaluationAssessment;
use App\Entity\EvaluationEvaluator;
use App\Entity\EvaluationEvaluatorSimple;
use App\Entity\EvaluationEvaluatorSumAggregate;
use App\Entity\User;
use App\Exception\UnsupportedEvaluationEvaluatorTypeException;
use App\Form\EvaluationEvaluatorSimpleType;
use App\Form\EvaluationEvaluatorSumAggregateType;
use Doctrine\ORM\EntityManagerInterface;

class EvaluationService
{
    private ?EntityManagerInterface $em = null;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @throws UnsupportedEvaluationEvaluatorTypeException
     */
    public function getEvaluatorImplementation(EvaluationEvaluator $evaluationEvaluator): EvaluationEvaluatorSimple|EvaluationEvaluatorSumAggregate
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
}
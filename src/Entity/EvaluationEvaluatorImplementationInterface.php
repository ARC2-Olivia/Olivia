<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

interface EvaluationEvaluatorImplementationInterface
{
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void;
    public function calculateResult(EvaluationAssessment $evaluationAssessment, ValidatorInterface $validator = null);
    public function checkConformity(EvaluationAssessment $evaluationAssessment, ValidatorInterface $validator = null): bool;
}
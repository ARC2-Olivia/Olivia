<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

interface EvaluationEvaluatorImplInterface
{
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void;
}
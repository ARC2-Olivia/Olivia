<?php

namespace App\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

interface PracticalSubmoduleProcessorImplementationInterface
{
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context, $payload): void;
    public function calculateResult(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null, TranslatorInterface $translator = null);
    public function checkConformity(PracticalSubmoduleAssessment $practicalSubmoduleAssessment, ValidatorInterface $validator = null, TranslatorInterface $translator = null): bool;
    public function getResultText(): ?string;
    public function setResultText(?string $resultText): self;
}
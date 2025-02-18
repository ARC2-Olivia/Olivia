<?php

namespace App\Validator;

use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class PageAndQuestionsValidator extends ConstraintValidator
{
    /**
     * @param Collection<int, PracticalSubmoduleQuestion> $value
     * @param PageAndQuestions $constraint
     * @return void
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!$constraint instanceof PageAndQuestions) {
            throw new UnexpectedTypeException($constraint, PageAndQuestions::class);
        }

        if (!$value instanceof Collection) {
            throw new UnexpectedValueException($value, Collection::class);
        }

        if ($value->isEmpty()) {
            $this->context->buildViolation($constraint->noQuestionsMessage)->addViolation();
        }

        if ($constraint->submoduleToMatch !== null) {
            foreach ($value as $question) {
                if ($constraint->submoduleToMatch !== $question->getPracticalSubmodule()) {
                    $this->context->buildViolation($constraint->submoduleMismatchMessage)->addViolation();
                }
            }
        }
    }
}

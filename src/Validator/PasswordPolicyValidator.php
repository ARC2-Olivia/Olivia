<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PasswordPolicyValidator extends ConstraintValidator
{
    /**
     * @param string $value
     * @param PasswordPolicy $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || strlen($value) < 8) {
            $this->context->buildViolation($constraint->minLengthMessage)->addViolation();
        }

        if (1 !== preg_match('/\d/', $value)) {
            $this->context->buildViolation($constraint->requiredNumberMessage)->addViolation();
        }

        if (1 !== preg_match('/[A-ZČĆŠĐŽ]/', $value)) {
            $this->context->buildViolation($constraint->requiredUppercaseMessage)->addViolation();
        }
    }
}

<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class PasswordPolicy extends Constraint
{
    public $minLengthMessage = 'error.passwordPolicy.length';
    public $requiredNumberMessage = 'error.passwordPolicy.number';
    public $requiredUppercaseMessage = 'error.passwordPolicy.uppercase';
}

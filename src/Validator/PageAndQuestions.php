<?php

namespace App\Validator;

use App\Entity\PracticalSubmodule;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 *
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY  | \Attribute::IS_REPEATABLE)]
class PageAndQuestions extends Constraint
{
    public $noQuestionsMessage = 'error.practicalSubmodulePage.questions';
    public $submoduleMismatchMessage = 'error.practicalSubmodulePage.submoduleMismatch';
    public ?PracticalSubmodule $submoduleToMatch = null;
}

<?php

namespace App\Validator;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\Translation\TranslatorInterface;

class CaptchaValidator extends ConstraintValidator
{
    private RequestStack $requestStack;
    private TranslatorInterface $translator;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    /**
     * @param mixed $value
     * @param Captcha $constraint
     * @return void
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Captcha) {
            throw new UnexpectedTypeException($constraint, Captcha::class);
        }

        if (empty($value)) {
            $this->context->buildViolation($this->translator->trans('error.captcha.empty', [], 'message'))->addViolation();
            return;
        }

        if (!is_numeric($value)) {
            throw new UnexpectedValueException($value, 'numeric');
        }

        $userResult = intval($value);
        $actualResult = intval($this->requestStack->getSession()->get('captcha_result'));

        if ($userResult !== $actualResult) {
            $this->context->buildViolation($this->translator->trans('error.captcha.incorrect', [], 'message'))->addViolation();
        }
    }
}
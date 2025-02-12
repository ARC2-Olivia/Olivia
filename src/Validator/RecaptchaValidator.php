<?php

namespace App\Validator;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RecaptchaValidator extends ConstraintValidator
{
    private ParameterBagInterface $parameterBag;
    private HttpClientInterface $httpClient;
    private TranslatorInterface $translator;

    public function __construct(ParameterBagInterface $parameterBag, HttpClientInterface $httpClient, TranslatorInterface $translator)
    {
        $this->parameterBag = $parameterBag;
        $this->httpClient = $httpClient;
        $this->translator = $translator;
    }

    /**
     * @param string $value
     * @param Recaptcha $constraint
     */
    public function validate(mixed $value, Constraint $constraint)
    {
        if (!is_string($value) && $value !== null) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if ($value === '' || $value === null) {
            $this->context->buildViolation($this->translator->trans('error.recaptcha.noData', [], 'message'))->addViolation();
            return;
        }

        $response = $this->httpClient->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->parameterBag->get('recaptcha.secret.key'),
                'response' => $value
            ]
        ]);

        $recaptchaValidation = json_decode($response->getContent());
        if (!$recaptchaValidation->success) {
            $this->context->buildViolation($this->translator->trans('error.recaptcha.validationFailed', [], 'message'))->addViolation();
        }
    }
}
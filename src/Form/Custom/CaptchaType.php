<?php

namespace App\Form\Custom;

use App\Validator\Captcha;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CaptchaType extends AbstractType
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('result', NumberType::class, ['constraints' => [new Captcha()]]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $firstNumber = random_int(1, 20);
        $secondNumber = random_int(1, 20);
        $result = $firstNumber + $secondNumber;
        $this->requestStack->getSession()->set('captcha_result', $result);

        $view->vars['captcha_1st_number'] = $firstNumber;
        $view->vars['captcha_2nd_number'] = $secondNumber;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $firstNumber = random_int(1, 20);
        $secondNumber = random_int(1, 20);
        $resolver->setDefault('mapped', false);
    }
}
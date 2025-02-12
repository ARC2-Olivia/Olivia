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
use Symfony\Contracts\Translation\TranslatorInterface;

class CaptchaType extends AbstractType
{
    private RequestStack $requestStack;
    private TranslatorInterface $translator;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
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

        $view->vars['captcha_1st_number'] = $this->translator->trans('number.'.$firstNumber, [], 'app');
        $view->vars['captcha_2nd_number'] = $this->translator->trans('number.'.$secondNumber, [], 'app');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $firstNumber = random_int(1, 20);
        $secondNumber = random_int(1, 20);
        $resolver->setDefault('mapped', false);
    }
}
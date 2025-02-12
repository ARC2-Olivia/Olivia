<?php

namespace App\Form\Custom;

use App\Validator\Recaptcha;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecaptchaType extends AbstractType
{
    private ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('token', HiddenType::class, ['constraints' => [new Recaptcha()]]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['google_recaptcha_site_key'] = $options['google_recaptcha_site_key'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('google_recaptcha_site_key', $this->parameterBag->get('recaptcha.site.key'))
            ->setDefault('mapped', false);
    }
}
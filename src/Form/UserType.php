<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class UserType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roleChoices = [User::ROLE_USER => User::ROLE_USER, User::ROLE_MODERATOR => User::ROLE_MODERATOR, User::ROLE_ADMIN => User::ROLE_ADMIN];
        $activatedChoices = [$this->translator->trans('common.yes', [], 'app') => true, $this->translator->trans('common.no', [], 'app') => false];

        $builder
            ->add('email', EmailType::class, ['label' => 'form.entity.user.label.email', 'attr' => ['class' => 'form-input mb-3']])
            ->add('roles', ChoiceType::class, ['label' => 'form.entity.user.label.roles', 'choices' => $roleChoices, 'multiple' => true, 'attr' => ['class' => 'form-select multiple mb-3']])
            ->add('activated', ChoiceType::class, ['label' => 'form.entity.user.label.activated', 'choices' => $activatedChoices, 'attr' => ['class' => 'form-select mb-3']])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'app',
            'attr' => ['novalidate' => 'novalidate']
        ]);
    }
}

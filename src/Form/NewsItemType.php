<?php

namespace App\Form;

use App\Entity\NewsItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewsItemType extends AbstractType
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'form.entity.newsItem.label.title',
                'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.newsItem.placeholder.title', [], 'app')]
            ])
            ->add('content', HiddenType::class, ['label' => 'form.entity.newsItem.label.content'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => NewsItem::class,
            'translation_domain' => 'app',
            'attr' => [
                'class' => 'd-flex flex-column',
                'novalidate' => 'novalidate'
            ]
        ]);
    }
}

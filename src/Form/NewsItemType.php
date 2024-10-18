<?php

namespace App\Form;

use App\Entity\NewsItem;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class NewsItemType extends AbstractType
{
    private ?TranslatorInterface $translator = null;
    private ?ParameterBagInterface $parameterBag = null;
    private ?RequestStack $requestStack = null;

    public function __construct(TranslatorInterface $translator, ParameterBagInterface $parameterBag, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->parameterBag = $parameterBag;
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $localeDefault = $this->parameterBag->get('locale.default');
        $localeAlternate = $this->parameterBag->get('locale.alternate');

        $languageChoices = [
            $this->translator->trans('newsItem.language.default', [], 'app') => $localeDefault,
            $this->translator->trans('newsItem.language.alternate', [], 'app') => $localeAlternate
        ];

        $data = $builder->getData();
        if ($data instanceof NewsItem && null === $data->getLanguage()) {
            $defaultLanguageChoice = $this->requestStack->getCurrentRequest()->getLocale() === $localeAlternate ? $localeAlternate : $localeDefault;
            $data->setLanguage($defaultLanguageChoice);
        }

        $builder
            ->add('title', TextType::class, [
                'label' => 'form.entity.newsItem.label.title',
                'attr' => ['class' => 'form-input mb-3', 'placeholder' => $this->translator->trans('form.entity.newsItem.placeholder.title', [], 'app')]
            ])
            ->add('content', HiddenType::class, ['label' => 'form.entity.newsItem.label.content'])
            ->add('language', ChoiceType::class, ['label' => 'form.entity.newsItem.label.language', 'choices' => $languageChoices, 'attr' => ['class' => 'form-select mb-3']])
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

<?php

namespace App\EventSubscriber;

use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private ?TranslatableListener $translatableListener = null;
    private ?ParameterBagInterface $parameterBag = null;
    private ?string $defaultLocale = null;

    public function __construct(TranslatableListener $translatableListener, ParameterBagInterface $parameterBag, string $defaultLocale = 'en')
    {
        $this->translatableListener = $translatableListener;
        $this->parameterBag = $parameterBag;
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $locale = null;
        $request = $event->getRequest();
        $localeDefault = $this->parameterBag->get('locale.default');
        $localeAlternate = $this->parameterBag->get('locale.alternate');

        if ($request->headers->has('Accept-Language')) {
            $localeHeader = $request->headers->get('Accept-Language');
            if ($localeAlternate === $localeHeader || $localeDefault === $localeHeader) {
                $locale = $localeHeader;
            }
        }

        if (null === $locale) {
            $locale = $event->getRequest()->getLocale() ?? $this->defaultLocale;
        }

        $this->translatableListener->setTranslatableLocale($locale);
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }
}
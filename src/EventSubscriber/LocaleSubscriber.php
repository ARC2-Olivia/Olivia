<?php

namespace App\EventSubscriber;

use Gedmo\Translatable\TranslatableListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    private ?TranslatableListener $translatableListener = null;
    private ?string $defaultLocale = null;

    public function __construct(TranslatableListener $translatableListener, string $defaultLocale = 'en')
    {
        $this->translatableListener = $translatableListener;
        $this->defaultLocale = $defaultLocale;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest() ?? $this->defaultLocale;
        $this->translatableListener->setTranslatableLocale($request->getLocale());
    }

    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }
}
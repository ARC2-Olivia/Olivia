<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AccessibilitySubscriber implements EventSubscriberInterface
{
    private const QUERY_PARAM = 'accessibilityToggle';

    private const TOGGLE_DYSLEXIA_MODE = 'dyslexiaMode';

    private const SESSION_DYSLEXIA_MODE = 'accessibility.dyslexiaMode';

    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $session = $event->getRequest()->getSession();
        $query = $event->getRequest()->query;
        if (!$query->has(self::QUERY_PARAM)) {
            return;
        }

        $accessibilityToggle = $event->getRequest()->query->get(self::QUERY_PARAM);
        if (self::TOGGLE_DYSLEXIA_MODE === $accessibilityToggle) {
            if ($session->has(self::SESSION_DYSLEXIA_MODE)) {
                $session->set(self::SESSION_DYSLEXIA_MODE, !$session->get(self::SESSION_DYSLEXIA_MODE));
            } else {
                $session->set(self::SESSION_DYSLEXIA_MODE, true);
            }
        }
    }
}
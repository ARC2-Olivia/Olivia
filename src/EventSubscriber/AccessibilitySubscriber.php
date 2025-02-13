<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AccessibilitySubscriber implements EventSubscriberInterface
{
    private const QUERY_PARAM = 'accessibilityToggle';

    private const TOGGLE_DYSLEXIA_MODE = 'dyslexiaMode';
    private const TOGGLE_CONTRAST_MODE = 'contrastMode';

    private const SESSION_DYSLEXIA_MODE = 'accessibility.dyslexiaMode';
    private const SESSION_CONTRAST_MODE = 'accessibility.contrastMode';

    public static function getSubscribedEvents()
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $query = $event->getRequest()->query;
        if (!$query->has(self::QUERY_PARAM)) {
            return;
        }

        $this->toggleAccessibilityOption($event, self::TOGGLE_DYSLEXIA_MODE, self::SESSION_DYSLEXIA_MODE);
        $this->toggleAccessibilityOption($event, self::TOGGLE_CONTRAST_MODE, self::SESSION_CONTRAST_MODE);
    }

    /**
     * @param RequestEvent $event
     * @param string $accessibilityToggle
     * @param string $accessibilitySessionKey
     * @return void
     */
    public function toggleAccessibilityOption(RequestEvent $event, string $accessibilityToggle, string $accessibilitySessionKey): void
    {
        if ($accessibilityToggle !== $event->getRequest()->query->get(self::QUERY_PARAM)) {
            return;
        }

        $session = $event->getRequest()->getSession();
        if ($session->has($accessibilitySessionKey)) {
            $session->set($accessibilitySessionKey, !$session->get($accessibilitySessionKey));
        } else {
            $session->set($accessibilitySessionKey, true);
        }
    }
}
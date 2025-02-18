<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class MaintenanceModeSubscriber implements EventSubscriberInterface
{
    private ?ParameterBagInterface $parameterBag = null;
    private ?RouterInterface $router = null;

    public function __construct(ParameterBagInterface $parameterBag, RouterInterface $router)
    {
        $this->parameterBag = $parameterBag;
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $maintenanceMode = $this->parameterBag->get('maintenance.mode');
        $route = $event->getRequest()->attributes->get('_route');
        if (true === $maintenanceMode && 'maintenance' !== $route) {
            $event->setResponse(new RedirectResponse($this->router->generate('maintenance')));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'onKernelRequest'];
    }
}

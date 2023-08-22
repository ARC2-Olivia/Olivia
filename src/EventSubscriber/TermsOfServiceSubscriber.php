<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\TermsOfServiceService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class TermsOfServiceSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private TermsOfServiceService $termsOfServiceService;
    private RouterInterface $router;

    public function __construct(Security $security, TermsOfServiceService $termsOfServiceService, RouterInterface $router)
    {
        $this->security = $security;
        $this->termsOfServiceService = $termsOfServiceService;
        $this->router = $router;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $route = $event->getRequest()->attributes->get('_route');

        if (!$this->isException($event->getRequest()) && $this->checkRoutes($route) && $this->isRegularUser() && !$this->termsOfServiceService->userAcceptedCurrentlyActiveTermsOfService($user)) {
            $event->setController(function () {
                return new RedirectResponse($this->router->generate('tos_active'));
            });
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    private function isRegularUser(): bool
    {
        return $this->security->isGranted(User::ROLE_USER) && !$this->security->isGranted(User::ROLE_ADMIN) && !$this->security->isGranted(User::ROLE_MODERATOR);
    }

    private function checkRoutes(?string $route): bool
    {
        return !in_array($route, ['_wdt', '_profiler', 'tos_active', 'security_logout', 'data_protection', 'tos_accept', 'data_request_access', 'data_request_resolve']);
    }

    private function isException(\Symfony\Component\HttpFoundation\Request $request): bool
    {
        return $request->attributes->has('exception');
    }
}

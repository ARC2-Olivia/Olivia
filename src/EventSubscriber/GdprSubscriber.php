<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\GdprService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class GdprSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private GdprService $termsOfServiceService;
    private RouterInterface $router;

    public function __construct(Security $security, GdprService $termsOfServiceService, RouterInterface $router)
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

        if (!$this->isException($event->getRequest()) && $this->checkRoutes($route) && $this->isRegularUser() && !$this->termsOfServiceService->userAcceptedCurrentlyActiveGdpr($user)) {
            $event->setController(function () {
                return new RedirectResponse($this->router->generate('gdpr_active_terms_of_service'));
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
        return !in_array($route, ['_wdt', '_profiler', 'gdpr_active_terms_of_service', 'gdpr_privacy_policy', 'security_logout', 'gdpr_accept', 'gdpr_data_protection', 'gdpr_data_protection_access', 'gdpr_data_protection_delete', 'profile', 'profile_edit_basic_data', 'profile_edit_password']);
    }

    private function isException(\Symfony\Component\HttpFoundation\Request $request): bool
    {
        return $request->attributes->has('exception');
    }
}

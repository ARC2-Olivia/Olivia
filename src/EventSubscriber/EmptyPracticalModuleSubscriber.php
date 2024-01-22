<?php

namespace App\EventSubscriber;

use App\Controller\PracticalSubmoduleAssessmentController;
use App\Controller\PracticalSubmoduleController;
use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;

class EmptyPracticalModuleSubscriber implements EventSubscriberInterface
{
    private Security $security;
    private EntityManagerInterface $em;
    private RouterInterface $router;

    public function __construct(Security $security, EntityManagerInterface $em, RouterInterface $router)
    {
        $this->security = $security;
        $this->em = $em;
        $this->router = $router;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$this->checkCondition()) {
            return;
        }

        $request = $event->getRequest();
        $controllerInfo = explode('::', $request->attributes->get('_controller'));

        if (count($controllerInfo) < 2) {
            return;
        }

        if ($controllerInfo[0] === PracticalSubmoduleController::class && null !== $psId = $request->attributes->get('practicalSubmodule')) {
            $this->handlePracticalSubmodule($event, $controllerInfo[1], $psId);
            return;
        }

        if ($controllerInfo[0] === PracticalSubmoduleAssessmentController::class && null !== $psaId = $request->attributes->get('practicalSubmoduleAssessment')) {
            $this->handlePracticalSubmoduleAssessment($event, $psaId);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER => 'onKernelController'];
    }

    private function checkCondition(): bool
    {
        return $this->security->isGranted('ROLE_USER')
            && !$this->security->isGranted('ROLE_MODERATOR')
            && !$this->security->isGranted('ROLE_ADMIN');
    }

    private function handlePracticalSubmodule(ControllerEvent $event, $action, $psId): void
    {
        $ps = $this->em->getRepository(PracticalSubmodule::class)->find($psId);
        if ('overview' !== $action && null !== $ps && !$ps->canRunAssessment()) {
            $event->setController(function () use ($psId) {
                return new RedirectResponse($this->router->generate('practical_submodule_overview', ['practicalSubmodule' => $psId]));
            });
        }
    }

    private function handlePracticalSubmoduleAssessment(ControllerEvent $event, $psaId): void
    {
        $psa = $this->em->getRepository(PracticalSubmoduleAssessment::class)->find($psaId);
        if (null !== $psa && !$psa->getPracticalSubmodule()->canRunAssessment()) {
            $psId = $psa->getPracticalSubmodule()->getId();
            $event->setController(function () use ($psId) {
                return new RedirectResponse($this->router->generate('practical_submodule_overview', ['practicalSubmodule' => $psId]));
            });
        }
    }
}

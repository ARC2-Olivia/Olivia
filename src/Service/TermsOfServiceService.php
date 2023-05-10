<?php

namespace App\Service;

use App\Entity\TermsOfService;
use App\Entity\User;
use App\Repository\TermsOfServiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class TermsOfServiceService
{
    private ?EntityManagerInterface $em = null;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function create(TermsOfService $termsOfService): void
    {
        $version = $this->em->getRepository(TermsOfService::class)->getLatestVersionNumber() + 1;
        $termsOfService->setVersion($version)->setRevision(0)->setStartedAt(new \DateTimeImmutable())->setActive(true);
        $this->em->persist($termsOfService);
        $this->em->flush();
    }

    public function deactivateCurrentlyActive(): void
    {
        $activeTermsOfService = $this->em->getRepository(TermsOfService::class)->findCurrentlyActive(false);
        /** @var TermsOfService $termsOfService */
        foreach ($activeTermsOfService as $termsOfService) {
            $termsOfService->setActive(false)->setEndedAt(new \DateTimeImmutable());
        }
        $this->em->flush();
    }

    public function userAcceptsTermsOfService(User $user, TermsOfService $termsOfService): void
    {
        // TODO: Implement accepting
    }

    public function userAcceptsCurrentlyActiveTermsOfService(User $user): void
    {
        $termsOfService = $this->em->getRepository(TermsOfService::class)->findCurrentlyActive();
        $this->userAcceptsTermsOfService($user, $termsOfService);
    }
}
<?php

namespace App\Service;

use App\Entity\AcceptedTermsOfService;
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

    public function create(TermsOfService $termsOfService): TermsOfService
    {
        $version = $this->em->getRepository(TermsOfService::class)->getLatestVersionNumber() + 1;
        $termsOfService->setVersion($version)->setRevision(0)->setStartedAt(new \DateTimeImmutable())->setActive(true);
        $this->em->persist($termsOfService);
        $this->em->flush();
        return $termsOfService;
    }

    public function revise(TermsOfService $revisedTermsOfService): TermsOfService
    {
        $termsOfServiceRepository = $this->em->getRepository(TermsOfService::class);
        $version = $termsOfServiceRepository->getLatestVersionNumber();
        $revision = $termsOfServiceRepository->getLatestRevisionNumberForVersion($version) + 1;
        $revisedTermsOfService->setVersion($version)->setRevision($revision)->setStartedAt(new \DateTimeImmutable())->setActive(true);
        $this->em->persist($revisedTermsOfService);
        return $revisedTermsOfService;
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
        if (!$this->userAcceptedTermsOfService($user, $termsOfService)) {
            $acceptedTermsOfService = (new AcceptedTermsOfService())->setUser($user)->setTermsOfService($termsOfService)->setAcceptedAt(new \DateTimeImmutable());
            $this->em->persist($acceptedTermsOfService);
            $this->em->flush();
        }
    }

    public function userAcceptsCurrentlyActiveTermsOfService(User $user): void
    {
        $termsOfService = $this->em->getRepository(TermsOfService::class)->findCurrentlyActive();
        $this->userAcceptsTermsOfService($user, $termsOfService);
    }

    public function userAcceptedTermsOfService(User $user, ?TermsOfService $termsOfService): bool
    {
        if ($termsOfService === null) return true;
        return $this->em->getRepository(AcceptedTermsOfService::class)->count(['user' => $user, 'termsOfService' => $termsOfService]) > 0;
    }

    public function userAcceptedCurrentlyActiveTermsOfService(User $user): bool
    {
        $termsOfService = $this->em->getRepository(TermsOfService::class)->findCurrentlyActive();
        return $this->userAcceptedTermsOfService($user, $termsOfService);
    }
}
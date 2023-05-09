<?php

namespace App\Service;

use App\Entity\TermsOfService;
use App\Repository\TermsOfServiceRepository;
use Doctrine\ORM\EntityManagerInterface;

class TermsOfServiceService
{
    private EntityManagerInterface $em;

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
}
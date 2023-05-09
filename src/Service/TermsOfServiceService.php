<?php

namespace App\Service;

use App\Entity\TermsOfService;
use App\Repository\TermsOfServiceRepository;

class TermsOfServiceService
{
    private TermsOfServiceRepository $termsOfServiceRepository;

    public function __construct(TermsOfServiceRepository $termsOfServiceRepository)
    {
        $this->termsOfServiceRepository = $termsOfServiceRepository;
    }

    public function create(TermsOfService $termsOfService): void
    {
        $version = $this->termsOfServiceRepository->getLatestVersionNumber() + 1;
        $termsOfService->setVersion($version)->setRevision(0)->setStartedAt(new \DateTimeImmutable());
        $this->termsOfServiceRepository->save($termsOfService, true);
    }
}
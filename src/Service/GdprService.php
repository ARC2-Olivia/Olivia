<?php

namespace App\Service;

use App\Entity\AcceptedGdpr;
use App\Entity\Gdpr;
use App\Entity\User;
use App\Repository\GdprRepository;
use Doctrine\ORM\EntityManagerInterface;

class GdprService
{
    private ?EntityManagerInterface $em = null;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function create(Gdpr $gdpr): Gdpr
    {
        $version = $this->em->getRepository(Gdpr::class)->getLatestVersionNumber() + 1;
        $gdpr->setVersion($version)->setRevision(0)->setStartedAt(new \DateTimeImmutable())->setActive(true);
        $this->em->persist($gdpr);
        $this->em->flush();
        return $gdpr;
    }

    public function revise(Gdpr $revisedGdpr): Gdpr
    {
        $gdprRepository = $this->em->getRepository(Gdpr::class);
        $version = $gdprRepository->getLatestVersionNumber();
        $revision = $gdprRepository->getLatestRevisionNumberForVersion($version) + 1;
        $revisedGdpr->setVersion($version)->setRevision($revision)->setStartedAt(new \DateTimeImmutable())->setActive(true);
        $this->em->persist($revisedGdpr);
        return $revisedGdpr;
    }

    public function deactivateCurrentlyActive(): void
    {
        /** @var Gdpr[] $activeGdpr */
        $activeGdpr = $this->em->getRepository(Gdpr::class)->findCurrentlyActive(false);
        foreach ($activeGdpr as $gdpr) {
            $gdpr->setActive(false)->setEndedAt(new \DateTimeImmutable());
        }
        $this->em->flush();
    }

    public function userAcceptsGdpr(User $user, Gdpr $gdpr): void
    {
        if (!$this->userAcceptedGdpr($user, $gdpr)) {
            $acceptedGdpr = (new AcceptedGdpr())->setUser($user)->setGdpr($gdpr)->setAcceptedAt(new \DateTimeImmutable());
            $this->em->persist($acceptedGdpr);
            $this->em->flush();
        }
    }

    public function userAcceptsCurrentlyActiveGdpr(User $user): void
    {
        $gdpr = $this->em->getRepository(Gdpr::class)->findCurrentlyActive();
        $this->userAcceptsGdpr($user, $gdpr);
    }

    public function userAcceptedGdpr(User $user, ?Gdpr $gdpr): bool
    {
        if ($gdpr === null) return true;
        return $this->em->getRepository(AcceptedGdpr::class)->count(['user' => $user, 'gdpr' => $gdpr]) > 0;
    }

    public function userAcceptedCurrentlyActiveGdpr(User $user): bool
    {
        $gdpr = $this->em->getRepository(Gdpr::class)->findCurrentlyActive();
        return $this->userAcceptedGdpr($user, $gdpr);
    }

    public function userRescindsGdpr(User $user, Gdpr $gdpr): void
    {
        $acceptedGdprs = $this->em->getRepository(AcceptedGdpr::class)->findBy(['user' => $user, 'gdpr' => $gdpr]);
        foreach ($acceptedGdprs as $acceptedGdpr) $this->em->remove($acceptedGdpr);
        $this->em->flush();
    }
}
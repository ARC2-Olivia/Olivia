<?php

namespace App\Repository;

use App\Entity\TermsOfService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TermsOfService>
 *
 * @method TermsOfService|null find($id, $lockMode = null, $lockVersion = null)
 * @method TermsOfService|null findOneBy(array $criteria, array $orderBy = null)
 * @method TermsOfService[]    findAll()
 * @method TermsOfService[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermsOfServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TermsOfService::class);
    }

    public function save(TermsOfService $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TermsOfService $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getLatestVersionNumber(): int
    {
        return $this->createQueryBuilder('tos')
            ->select('COALESCE(MAX(tos.version), 0)')
            ->getQuery()->getSingleScalarResult()
        ;
    }

    public function getLatestRevisionNumberForVersion(int $version): int
    {
        return $this->createQueryBuilder('tos')
            ->select('COALESCE(MAX(tos.revision), 0)')
            ->where('tos.version = :version')
            ->setParameter('version', $version)
            ->getQuery()->getSingleScalarResult()
        ;
    }
}

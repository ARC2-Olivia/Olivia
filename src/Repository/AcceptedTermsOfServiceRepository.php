<?php

namespace App\Repository;

use App\Entity\AcceptedTermsOfService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AcceptedTermsOfService>
 *
 * @method AcceptedTermsOfService|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcceptedTermsOfService|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcceptedTermsOfService[]    findAll()
 * @method AcceptedTermsOfService[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcceptedTermsOfServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcceptedTermsOfService::class);
    }

    public function save(AcceptedTermsOfService $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AcceptedTermsOfService $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

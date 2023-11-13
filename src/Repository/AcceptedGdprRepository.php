<?php

namespace App\Repository;

use App\Entity\AcceptedGdpr;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AcceptedGdpr>
 *
 * @method AcceptedGdpr|null find($id, $lockMode = null, $lockVersion = null)
 * @method AcceptedGdpr|null findOneBy(array $criteria, array $orderBy = null)
 * @method AcceptedGdpr[]    findAll()
 * @method AcceptedGdpr[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AcceptedGdprRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcceptedGdpr::class);
    }

    public function save(AcceptedGdpr $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AcceptedGdpr $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

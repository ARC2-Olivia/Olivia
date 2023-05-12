<?php

namespace App\Repository;

use App\Entity\DataRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DataRequest>
 *
 * @method DataRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method DataRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method DataRequest[]    findAll()
 * @method DataRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DataRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DataRequest::class);
    }

    public function save(DataRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DataRequest $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}

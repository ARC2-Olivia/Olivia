<?php

namespace App\Repository;

use App\Entity\PracticalSubmoduleProcessorMaxValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleProcessorMaxValue>
 *
 * @method PracticalSubmoduleProcessorMaxValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleProcessorMaxValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleProcessorMaxValue[]    findAll()
 * @method PracticalSubmoduleProcessorMaxValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleProcessorMaxValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleProcessorMaxValue::class);
    }

    public function save(PracticalSubmoduleProcessorMaxValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleProcessorMaxValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return PracticalSubmoduleProcessorMaxValue[] Returns an array of PracticalSubmoduleProcessorMaxValue objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PracticalSubmoduleProcessorMaxValue
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

<?php

namespace App\Repository;

use App\Entity\PracticalSubmoduleProcessorResultCombiner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleProcessorResultCombiner>
 *
 * @method PracticalSubmoduleProcessorResultCombiner|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleProcessorResultCombiner|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleProcessorResultCombiner[]    findAll()
 * @method PracticalSubmoduleProcessorResultCombiner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleProcessorResultCombinerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleProcessorResultCombiner::class);
    }

    public function save(PracticalSubmoduleProcessorResultCombiner $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleProcessorResultCombiner $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return PracticalSubmoduleProcessorResultCombiner[] Returns an array of PracticalSubmoduleProcessorResultCombiner objects
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

//    public function findOneBySomeField($value): ?PracticalSubmoduleProcessorResultCombiner
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

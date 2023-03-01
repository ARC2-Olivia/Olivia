<?php

namespace App\Repository;

use App\Entity\PracticalSubmoduleProcessorSumAggregate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleProcessorSumAggregate>
 *
 * @method PracticalSubmoduleProcessorSumAggregate|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleProcessorSumAggregate|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleProcessorSumAggregate[]    findAll()
 * @method PracticalSubmoduleProcessorSumAggregate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleProcessorSumAggregateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleProcessorSumAggregate::class);
    }

    public function save(PracticalSubmoduleProcessorSumAggregate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleProcessorSumAggregate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return EvaluationEvaluatorSumAggregate[] Returns an array of EvaluationEvaluatorSumAggregate objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?EvaluationEvaluatorSumAggregate
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

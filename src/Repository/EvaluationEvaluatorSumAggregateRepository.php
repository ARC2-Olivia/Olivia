<?php

namespace App\Repository;

use App\Entity\EvaluationEvaluatorSumAggregate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvaluationEvaluatorSumAggregate>
 *
 * @method EvaluationEvaluatorSumAggregate|null find($id, $lockMode = null, $lockVersion = null)
 * @method EvaluationEvaluatorSumAggregate|null findOneBy(array $criteria, array $orderBy = null)
 * @method EvaluationEvaluatorSumAggregate[]    findAll()
 * @method EvaluationEvaluatorSumAggregate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvaluationEvaluatorSumAggregateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvaluationEvaluatorSumAggregate::class);
    }

    public function save(EvaluationEvaluatorSumAggregate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EvaluationEvaluatorSumAggregate $entity, bool $flush = false): void
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

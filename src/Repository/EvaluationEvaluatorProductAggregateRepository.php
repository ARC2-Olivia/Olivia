<?php

namespace App\Repository;

use App\Entity\EvaluationEvaluatorProductAggregate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvaluationEvaluatorProductAggregate>
 *
 * @method EvaluationEvaluatorProductAggregate|null find($id, $lockMode = null, $lockVersion = null)
 * @method EvaluationEvaluatorProductAggregate|null findOneBy(array $criteria, array $orderBy = null)
 * @method EvaluationEvaluatorProductAggregate[]    findAll()
 * @method EvaluationEvaluatorProductAggregate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvaluationEvaluatorProductAggregateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvaluationEvaluatorProductAggregate::class);
    }

    public function save(EvaluationEvaluatorProductAggregate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EvaluationEvaluatorProductAggregate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return EvaluationEvaluatorProductAggregate[] Returns an array of EvaluationEvaluatorProductAggregate objects
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

//    public function findOneBySomeField($value): ?EvaluationEvaluatorProductAggregate
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

<?php

namespace App\Repository;

use App\Entity\PracticalSubmoduleProcessorProductAggregate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleProcessorProductAggregate>
 *
 * @method PracticalSubmoduleProcessorProductAggregate|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleProcessorProductAggregate|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleProcessorProductAggregate[]    findAll()
 * @method PracticalSubmoduleProcessorProductAggregate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleProcessorProductAggregateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleProcessorProductAggregate::class);
    }

    public function save(PracticalSubmoduleProcessorProductAggregate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleProcessorProductAggregate $entity, bool $flush = false): void
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

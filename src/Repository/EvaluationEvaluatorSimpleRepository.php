<?php

namespace App\Repository;

use App\Entity\EvaluationEvaluatorSimple;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvaluationEvaluatorSimple>
 *
 * @method EvaluationEvaluatorSimple|null find($id, $lockMode = null, $lockVersion = null)
 * @method EvaluationEvaluatorSimple|null findOneBy(array $criteria, array $orderBy = null)
 * @method EvaluationEvaluatorSimple[]    findAll()
 * @method EvaluationEvaluatorSimple[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvaluationEvaluatorSimpleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvaluationEvaluatorSimple::class);
    }

    public function save(EvaluationEvaluatorSimple $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EvaluationEvaluatorSimple $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return EvaluationEvaluatorSimple[] Returns an array of EvaluationEvaluatorSimple objects
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

//    public function findOneBySomeField($value): ?EvaluationEvaluatorSimple
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

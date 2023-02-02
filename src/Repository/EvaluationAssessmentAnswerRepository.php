<?php

namespace App\Repository;

use App\Entity\EvaluationAssessmentAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvaluationAssessmentAnswer>
 *
 * @method EvaluationAssessmentAnswer|null find($id, $lockMode = null, $lockVersion = null)
 * @method EvaluationAssessmentAnswer|null findOneBy(array $criteria, array $orderBy = null)
 * @method EvaluationAssessmentAnswer[]    findAll()
 * @method EvaluationAssessmentAnswer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvaluationAssessmentAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvaluationAssessmentAnswer::class);
    }

    public function save(EvaluationAssessmentAnswer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EvaluationAssessmentAnswer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return EvaluationAssessmentAnswer[] Returns an array of EvaluationAssessmentAnswer objects
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

//    public function findOneBySomeField($value): ?EvaluationAssessmentAnswer
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

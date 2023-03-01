<?php

namespace App\Repository;

use App\Entity\PracticalSubmoduleAssessmentAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleAssessmentAnswer>
 *
 * @method PracticalSubmoduleAssessmentAnswer|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleAssessmentAnswer|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleAssessmentAnswer[]    findAll()
 * @method PracticalSubmoduleAssessmentAnswer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleAssessmentAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleAssessmentAnswer::class);
    }

    public function save(PracticalSubmoduleAssessmentAnswer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleAssessmentAnswer $entity, bool $flush = false): void
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

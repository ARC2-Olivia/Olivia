<?php

namespace App\Repository;

use App\Entity\Evaluation;
use App\Entity\EvaluationEvaluator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvaluationEvaluator>
 *
 * @method EvaluationEvaluator|null find($id, $lockMode = null, $lockVersion = null)
 * @method EvaluationEvaluator|null findOneBy(array $criteria, array $orderBy = null)
 * @method EvaluationEvaluator[]    findAll()
 * @method EvaluationEvaluator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvaluationEvaluatorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvaluationEvaluator::class);
    }

    public function save(EvaluationEvaluator $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EvaluationEvaluator $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOrderedForEvaluation(Evaluation $evaluation)
    {
        return $this->createQueryBuilder('ee')
            ->where('ee.evaluation = :evaluation')
            ->orderBy('ee.position', 'ASC')
            ->setParameter('evaluation', $evaluation)
            ->getQuery()->getResult();
    }
}

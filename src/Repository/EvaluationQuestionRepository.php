<?php

namespace App\Repository;

use App\Entity\Evaluation;
use App\Entity\EvaluationQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EvaluationQuestion>
 *
 * @method EvaluationQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method EvaluationQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method EvaluationQuestion[]    findAll()
 * @method EvaluationQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvaluationQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvaluationQuestion::class);
    }

    public function save(EvaluationQuestion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EvaluationQuestion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOrderedForEvaluation(Evaluation $evaluation)
    {
        return $this->createQueryBuilder('eq')
            ->where('eq.evaluation = :evaluation')
            ->orderBy('eq.position', 'ASC')
            ->setParameter('evaluation', $evaluation)
            ->getQuery()->getResult();
    }
}

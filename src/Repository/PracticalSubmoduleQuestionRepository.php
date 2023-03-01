<?php

namespace App\Repository;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleQuestion>
 *
 * @method PracticalSubmoduleQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleQuestion[]    findAll()
 * @method PracticalSubmoduleQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleQuestion::class);
    }

    public function save(PracticalSubmoduleQuestion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleQuestion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOrderedForEvaluation(PracticalSubmodule $evaluation)
    {
        return $this->createQueryBuilder('eq')
            ->where('eq.evaluation = :evaluation')
            ->orderBy('eq.position', 'ASC')
            ->setParameter('evaluation', $evaluation)
            ->getQuery()->getResult();
    }
}

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

    public function findOrderedForSubmodule(PracticalSubmodule $practicalSubmodule)
    {
        return $this->createQueryBuilder('psq')
            ->where('psq.practicalSubmodule = :submodule')
            ->orderBy('psq.position', 'ASC')
            ->setParameter('submodule', $practicalSubmodule)
            ->getQuery()->getResult();
    }

    public function maxPositionForSubmodule(PracticalSubmodule $practicalSubmodule): int
    {
        return $this->createQueryBuilder('psq')
            ->select('COALESCE(MAX(psq.position), 0)')
            ->where('psq.practicalSubmodule = :submodule')
            ->setParameter('submodule', $practicalSubmodule)
            ->getQuery()->getSingleScalarResult();
    }
}

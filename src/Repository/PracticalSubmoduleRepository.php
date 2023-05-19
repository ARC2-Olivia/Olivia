<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\PracticalSubmodule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmodule>
 *
 * @method PracticalSubmodule|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmodule|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmodule[]    findAll()
 * @method PracticalSubmodule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmodule::class);
    }

    public function save(PracticalSubmodule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmodule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findContainingCourse(Course $course)
    {
        return $this->createQueryBuilder('ps')
            ->leftJoin('ps.courses', 'c')
            ->where('c = :course')
            ->setParameter('course', $course)
            ->getQuery()->getResult();
    }
}

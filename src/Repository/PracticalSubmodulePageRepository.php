<?php

namespace App\Repository;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmodulePage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmodulePage>
 *
 * @method PracticalSubmodulePage|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmodulePage|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmodulePage[]    findAll()
 * @method PracticalSubmodulePage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmodulePageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmodulePage::class);
    }

    public function save(PracticalSubmodulePage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmodulePage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOrderedForSubmodule(PracticalSubmodule $practicalSubmodule)
    {
        return $this->createQueryBuilder('psp')
            ->where('psp.practicalSubmodule = :submodule')
            ->orderBy('psp.position', 'ASC')
            ->setParameter('submodule', $practicalSubmodule)
            ->getQuery()->getResult();
    }

    public function maxPositionForSubmodule(PracticalSubmodule $practicalSubmodule): int
    {
        return $this->createQueryBuilder('psp')
            ->select('COALESCE(MAX(psp.position), 0)')
            ->where('psp.practicalSubmodule = :submodule')
            ->setParameter('submodule', $practicalSubmodule)
            ->getQuery()->getSingleScalarResult();
    }
}

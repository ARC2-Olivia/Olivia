<?php

namespace App\Repository;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleProcessorGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleProcessorGroup>
 *
 * @method PracticalSubmoduleProcessorGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleProcessorGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleProcessorGroup[]    findAll()
 * @method PracticalSubmoduleProcessorGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleProcessorGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleProcessorGroup::class);
    }

    public function save(PracticalSubmoduleProcessorGroup $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleProcessorGroup $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param PracticalSubmodule $practicalSubmodule
     * @return PracticalSubmoduleProcessorGroup[]
     */
    public function findOrderedForSubmodule(PracticalSubmodule $practicalSubmodule)
    {
        return $this->createQueryBuilder('pspg')
            ->where('pspg.practicalSubmodule = :submodule')
            ->orderBy('pspg.position', 'ASC')
            ->setParameter('submodule', $practicalSubmodule)
            ->getQuery()->getResult();
    }

    public function maxPositionForSubmodule(PracticalSubmodule $practicalSubmodule): int
    {
        return $this->createQueryBuilder('pspg')
            ->select('COALESCE(MAX(pspg.position), 0)')
            ->where('pspg.practicalSubmodule = :submodule')
            ->setParameter('submodule', $practicalSubmodule)
            ->getQuery()->getSingleScalarResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleProcessor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleProcessor>
 *
 * @method PracticalSubmoduleProcessor|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleProcessor|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleProcessor[]    findAll()
 * @method PracticalSubmoduleProcessor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleProcessorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleProcessor::class);
    }

    public function save(PracticalSubmoduleProcessor $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleProcessor $entity, bool $flush = false): void
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

    /** @return PracticalSubmoduleProcessor[] */
    public function findRunnableProcessors(PracticalSubmodule $practicalSubmodule)
    {
        $qb = $this->createQueryBuilder('psp')
            ->where('psp.practicalSubmodule = :submodule')
            ->andWhere('psp.included = :true')
            ->andWhere('psp.disabled = :false OR psp.disabled IS NULL')
            ->setParameters(['submodule' => $practicalSubmodule, 'true' => true, 'false' => false])
            ->orderBy('psp.position', 'ASC');

        if ($practicalSubmodule::EXPORT_TYPE_PRIVACY_POLICY === $practicalSubmodule->getExportType()) {
            $qb->andWhere('psp.practicalSubmoduleProcessorGroup IS NOT NULL');
        }

        return $qb->getQuery()->getResult();
    }

    public function maxPositionForSubmodule(PracticalSubmodule $practicalSubmodule): int
    {
        return $this->createQueryBuilder('psp')
            ->select('COALESCE(MAX(psp.position), 0)')
            ->where('psp.practicalSubmodule = :submodule')
            ->setParameter('submodule', $practicalSubmodule)
            ->getQuery()->getSingleScalarResult()
        ;
    }
}

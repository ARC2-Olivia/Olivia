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

    public function findOrderedForSubmodule(PracticalSubmodule $practicalSubmodule, bool $ignoreDisabled = false)
    {
        $qb = $this->createQueryBuilder('psq')
            ->where('psq.practicalSubmodule = :submodule')
            ->orderBy('psq.position', 'ASC')
            ->setParameter('submodule', $practicalSubmodule)
        ;

        if (true === $ignoreDisabled) {
            $qb->andWhere('psq.disabled = :false OR psq.disabled IS NULL')->setParameter('false', false);
        }

        return $qb->getQuery()->getResult();
    }

    public function maxPositionForSubmodule(PracticalSubmodule $practicalSubmodule): int
    {
        return $this->createQueryBuilder('psq')
            ->select('COALESCE(MAX(psq.position), 0)')
            ->where('psq.practicalSubmodule = :submodule')
            ->setParameter('submodule', $practicalSubmodule)
            ->getQuery()->getSingleScalarResult();
    }

    public function findDependingQuestionTexts(int $dependentQuestionId, ?array $exclusions = null): array
    {
        $qb = $this->createQueryBuilder('psq')
            ->select('psq.questionText')
            ->leftJoin('psq.dependentPracticalSubmoduleQuestion', 'dpsq')
            ->where('dpsq.id = :dependentQuestionId')
            ->setParameter('dependentQuestionId', $dependentQuestionId)
        ;

        if (false === empty($exclusions)) {
            $qb->andWhere('psq.id NOT IN (:exclusions)')->setParameter('exclusions', $exclusions);
        }

        return array_map(function ($item) { return $item['questionText']; }, $qb->getQuery()->getResult());
    }

    public function countActualQuestions(PracticalSubmodule $practicalSubmodule): int
    {
        return $this->createQueryBuilder('psq')
            ->select('COUNT(psq.id)')
            ->where('psq.type != :type')
            ->andWhere('psq.practicalSubmodule = :submodule')
            ->setParameter('type', PracticalSubmoduleQuestion::TYPE_STATIC_TEXT)
            ->setParameter('submodule', $practicalSubmodule)
            ->getQuery()->getSingleScalarResult()
        ;
    }
}

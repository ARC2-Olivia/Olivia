<?php

namespace App\Repository;

use App\Entity\TermsOfService;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

/**
 * @extends ServiceEntityRepository<TermsOfService>
 *
 * @method TermsOfService|null find($id, $lockMode = null, $lockVersion = null)
 * @method TermsOfService|null findOneBy(array $criteria, array $orderBy = null)
 * @method TermsOfService[]    findAll()
 * @method TermsOfService[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermsOfServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TermsOfService::class);
    }

    public function save(TermsOfService $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TermsOfService $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findCurrentlyActive(bool $single = true): TermsOfService|array|null
    {
        $qb = $this->createQueryBuilder('tos')
            ->where('tos.active = :true')
            ->setParameter('true', true)
            ->orderBy('tos.startedAt', 'DESC')
        ;
        if ($single) return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
        return $qb->getQuery()->getResult();
    }

    public function getLatestVersionNumber(): int
    {
        return $this->createQueryBuilder('tos')
            ->select('COALESCE(MAX(tos.version), 0)')
            ->getQuery()->getSingleScalarResult()
        ;
    }

    public function getLatestRevisionNumberForVersion(int $version): int
    {
        return $this->createQueryBuilder('tos')
            ->select('COALESCE(MAX(tos.revision), 0)')
            ->where('tos.version = :version')
            ->setParameter('version', $version)
            ->getQuery()->getSingleScalarResult()
        ;
    }

    public function findByIdForLocale(int $id, string $locale): TermsOfService|null
    {
        $query = $this->createQueryBuilder('tos')
            ->where('tos.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
        ;
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        return $query->getOneOrNullResult();
    }

    public function dumpForDataAccess(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        try {
            $stmt = $conn->prepare('SELECT tos.id, tos.version, tos.revision, tos.started_at, tos.ended_at, tos.content, tos.active, atos.accepted_at FROM terms_of_service tos LEFT JOIN accepted_terms_of_service atos ON atos.terms_of_service_id = tos.id WHERE atos.user_id = :userId');
            $result = $stmt->executeQuery(['userId' => $user->getId()]);
            return $result->fetchAllAssociative();
        } catch (Exception $e) {
            return [];
        }
    }
}

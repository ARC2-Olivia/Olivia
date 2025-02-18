<?php

namespace App\Repository;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleAssessment>
 *
 * @method PracticalSubmoduleAssessment|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleAssessment|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleAssessment[]    findAll()
 * @method PracticalSubmoduleAssessment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleAssessmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleAssessment::class);
    }

    public function save(PracticalSubmoduleAssessment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleAssessment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return PracticalSubmoduleAssessment[] */
    public function findByUserWithLocale(User $user, string $locale): array {
        $query = $this->createQueryBuilder('psa')->where('psa.user = :user')->setParameter('user', $user)->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        return $query->getResult();
    }

    public function findOneByUserAndPracticalSubmoduleWithLocale(User $user, PracticalSubmodule $practicalSubmodule, string $locale): ?PracticalSubmoduleAssessment {
        $query = $this->createQueryBuilder('psa')
            ->where('psa.user = :user')->andWhere('psa.practicalSubmodule = :practicalSubmodule')
            ->setParameter('user', $user)->setParameter('practicalSubmodule', $practicalSubmodule)
            ->setMaxResults(1)->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        return $query->getOneOrNullResult();
    }

    public function dumpForDataAccess(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        try {
            $stmt = $conn->prepare('SELECT psa.id, ps.id as practical_submodule_id, ps.name as practical_submodule_name, psa.taken_at, psa.last_submitted_at, psa.completed FROM practical_submodule_assessment psa LEFT JOIN practical_submodule ps ON psa.practical_submodule_id = ps.id WHERE psa.user_id = :userId');
            $result = $stmt->executeQuery(['userId' => $user->getId()]);
            return $result->fetchAllAssociative();
        } catch (Exception $e) {
            return [];
        }
    }
}

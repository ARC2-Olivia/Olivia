<?php

namespace App\Repository;

use App\Entity\LessonCompletion;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LessonCompletion>
 *
 * @method LessonCompletion|null find($id, $lockMode = null, $lockVersion = null)
 * @method LessonCompletion|null findOneBy(array $criteria, array $orderBy = null)
 * @method LessonCompletion[]    findAll()
 * @method LessonCompletion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonCompletionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonCompletion::class);
    }

    public function save(LessonCompletion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LessonCompletion $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function dumpForDataAccess(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        try {
            $stmt = $conn->prepare('SELECT lc.id, lc.lesson_id, l.name as lesson_name, lc.completed FROM lesson_completion lc LEFT JOIN lesson l ON lc.lesson_id = l.id WHERE lc.user_id = :userId');
            $result = $stmt->executeQuery(['userId' => $user->getId()]);
            return $result->fetchAllAssociative();
        } catch (Exception $e) {
            return [];
        }
    }
}

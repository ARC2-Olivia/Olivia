<?php

namespace App\Repository;

use App\Entity\EduLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EduLog>
 *
 * @method EduLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method EduLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method EduLog[]    findAll()
 * @method EduLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EduLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EduLog::class);
    }

    public function save(EduLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(EduLog $entity, bool $flush = false): void
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
            $stmt = $conn->prepare('SELECT el.id, el.at, el.course_id, el.lesson_id, el.action, el.ip_address FROM edu_log el WHERE el.user_id = :userId');
            $result = $stmt->executeQuery(['userId' => $user->getId()]);
            return $result->fetchAllAssociative();
        } catch (Exception $e) {
            return [];
        }
    }
}

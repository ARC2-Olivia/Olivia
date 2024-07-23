<?php

namespace App\Repository;

use App\Entity\Enrollment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enrollment>
 *
 * @method Enrollment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Enrollment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Enrollment[]    findAll()
 * @method Enrollment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enrollment::class);
    }

    public function save(Enrollment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Enrollment $entity, bool $flush = false): void
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
            $stmt = $conn->prepare('SELECT e.id, e.course_id, c.name as course_name, e.enrolled_at, e.passed FROM enrollment e LEFT JOIN course c ON e.course_id = c.id WHERE e.user_id = :userId');
            $result = $stmt->executeQuery(['userId' => $user->getId()]);
            return $result->fetchAllAssociative();
        } catch (Exception $e) {
            return [];
        }
    }
}

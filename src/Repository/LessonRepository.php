<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Lesson>
 *
 * @method Lesson|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lesson|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lesson[]    findAll()
 * @method Lesson[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    public function save(Lesson $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Lesson $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function nextPositionInCourse(Course $course): int
    {
        return $this->createQueryBuilder('l')
            ->select('COALESCE(MAX(l.position), 1)')
            ->where('l.course = :course')
            ->setParameter('course', $course)
            ->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Course $course
     * @return Lesson[]
     */
    public function findAllForCourseSortedByPosition(Course $course): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.course = :course')
            ->setParameter('course', $course)
            ->orderBy('l.position', 'ASC')
            ->getQuery()->getResult();
    }
}

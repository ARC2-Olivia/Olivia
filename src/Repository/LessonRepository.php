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
        $max = $this->createQueryBuilder('l')
            ->select('COALESCE(MAX(l.position), 1)')
            ->where('l.course = :course')
            ->setParameter('course', $course)
            ->getQuery()->getSingleScalarResult();
        return $max + 1;
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

    public function findPreviousLesson(Lesson $lesson): ?Lesson
    {
        $previousPosition = $lesson->getPosition() !== null ? $lesson->getPosition() - 1 : 0;
        return $this->createQueryBuilder('l')
            ->where('l.course = :course')->andWhere('l.position = :previousPosition')
            ->setParameters(['course' => $lesson->getCourse(), 'previousPosition' => $previousPosition])
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    public function findNextLesson(Lesson $lesson): ?Lesson
    {
        $nextPosition = $lesson->getPosition() !== null ? $lesson->getPosition() + 1 : 0;
        return $this->createQueryBuilder('l')
            ->where('l.course = :course')->andWhere('l.position = :nextPosition')
            ->setParameters(['course' => $lesson->getCourse(), 'nextPosition' => $nextPosition])
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Course $course
     * @return Lesson[]
     */
    public function findQuizLessonsForCourse(Course $course): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.course = :course')->andWhere('l.type = :type')
            ->setParameters(['course' => $course, 'type' => Lesson::TYPE_QUIZ])
            ->getQuery()->getResult();
    }
}

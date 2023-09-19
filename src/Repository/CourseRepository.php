<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Topic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Course>
 *
 * @method Course|null find($id, $lockMode = null, $lockVersion = null)
 * @method Course|null findOneBy(array $criteria, array $orderBy = null)
 * @method Course[]    findAll()
 * @method Course[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    public function save(Course $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Course $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findContainingTopic(Topic $topic)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.topic', 't')
            ->where('t = :topic')
            ->setParameter('topic', $topic)
            ->getQuery()->getResult()
        ;
    }

    public function findOrderedByPosition()
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.position', 'ASC')
            ->getQuery()->getResult()
        ;
    }

    public function findContainingTopicAndOrderedByPosition(Topic $topic)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.topic', 't')
            ->where('t = :topic')
            ->setParameter('topic', $topic)
            ->orderBy('c.position', 'ASC')
            ->getQuery()->getResult()
        ;
    }
}

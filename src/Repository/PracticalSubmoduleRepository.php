<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\PracticalSubmodule;
use App\Entity\Topic;
use App\Repository\Trait\FindOneByIdWithLocaleTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

/**
 * @extends ServiceEntityRepository<PracticalSubmodule>
 *
 * @method PracticalSubmodule|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmodule|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmodule[]    findAll()
 * @method PracticalSubmodule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleRepository extends ServiceEntityRepository
{
    use FindOneByIdWithLocaleTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmodule::class);
    }

    public function save(PracticalSubmodule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmodule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findContainingCourse(Course $course)
    {
        return $this->createQueryBuilder('ps')
            ->leftJoin('ps.courses', 'c')
            ->where('c = :course')
            ->setParameter('course', $course)
            ->getQuery()->getResult();
    }

    public function findContainingTopic(Topic $topic)
    {
        return $this->createQueryBuilder('ps')
            ->leftJoin('ps.topic', 't')
            ->where('t = :topic')
            ->setParameter('topic', $topic)
            ->getQuery()->getResult()
        ;
    }

    public function findByIdForLocale(int $id, string $locale): PracticalSubmodule|null
    {
        $query = $this->createQueryBuilder('ps')
            ->where('ps.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
        ;
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        return $query->getOneOrNullResult();
    }

    public function findOrderedByPosition()
    {
        return $this->createQueryBuilder('ps')
            ->orderBy('ps.position', 'ASC')
            ->getQuery()->getResult()
        ;
    }

    public function findContainingTopicAndOrderedByPosition(Topic $topic)
    {
        return $this->createQueryBuilder('ps')
            ->leftJoin('ps.topic', 't')
            ->where('t = :topic')
            ->setParameter('topic', $topic)
            ->orderBy('ps.position', 'ASC')
            ->getQuery()->getResult()
        ;
    }
}

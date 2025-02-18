<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Topic;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

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

    public function findByIdForLocale(int $id, string $locale): Course|null
    {
        $query = $this->createQueryBuilder('c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
        ;
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        return $query->getOneOrNullResult();
    }

    /**
     * @param string $locale
     * @return Course[]
     */
    public function findAllForLocale(string $locale): array
    {
        $query = $this->createQueryBuilder('c')->orderBy('c.position', 'ASC')->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        return $query->getResult();
    }

    public function findPassedByUserAndOrderedByPosition(User $user, string $locale = null)
    {
        $query = $this->createQueryBuilder('c')
            ->leftJoin('c.enrollments', 'e')
            ->where('e.user = :user')->andWhere('e.passed = :passed')
            ->setParameters(['user' => $user, 'passed' => true])
            ->orderBy('c.position', 'ASC')
            ->getQuery();

        if (null !== $locale) {
            $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
            $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        }

        return $query->getResult();
    }

    public function findNotPassedByUserAndOrderedByPosition(User $user)
    {
        $passedIds = $this->createQueryBuilder('c')
            ->select('c.id')
            ->leftJoin('c.enrollments', 'e')
            ->where('e.user = :user')->andWhere('e.passed = :passed')
            ->setParameters(['user' => $user, 'passed' => true])
            ->orderBy('c.position', 'ASC')
            ->getQuery()->getResult();

        return $this->createQueryBuilder('c')
            ->where('c.id NOT IN (:passedIds)')
            ->setParameter('passedIds', $passedIds)
            ->orderBy('c.position', 'ASC')
            ->getQuery()->getResult();
    }
}

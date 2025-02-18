<?php

namespace App\Repository;

use App\Entity\LessonItemEmbeddedVideo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LessonItemEmbeddedVideo>
 *
 * @method LessonItemEmbeddedVideo|null find($id, $lockMode = null, $lockVersion = null)
 * @method LessonItemEmbeddedVideo|null findOneBy(array $criteria, array $orderBy = null)
 * @method LessonItemEmbeddedVideo[]    findAll()
 * @method LessonItemEmbeddedVideo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonItemEmbeddedVideoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonItemEmbeddedVideo::class);
    }

    public function save(LessonItemEmbeddedVideo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LessonItemEmbeddedVideo $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return LessonItemEmbeddedVideo[] Returns an array of LessonItemEmbeddedVideo objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?LessonItemEmbeddedVideo
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

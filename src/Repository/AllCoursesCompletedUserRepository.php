<?php

namespace App\Repository;

use App\Entity\AllCoursesCompletedUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AllCoursesCompletedUser>
 *
 * @method AllCoursesCompletedUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method AllCoursesCompletedUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method AllCoursesCompletedUser[]    findAll()
 * @method AllCoursesCompletedUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AllCoursesCompletedUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AllCoursesCompletedUser::class);
    }

    public function save(AllCoursesCompletedUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AllCoursesCompletedUser $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return AllCoursesCompletedUser[] Returns an array of AllCoursesCompletedUser objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?AllCoursesCompletedUser
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

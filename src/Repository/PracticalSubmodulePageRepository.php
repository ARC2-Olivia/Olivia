<?php

namespace App\Repository;

use App\Entity\PracticalSubmodulePage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmodulePage>
 *
 * @method PracticalSubmodulePage|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmodulePage|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmodulePage[]    findAll()
 * @method PracticalSubmodulePage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmodulePageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmodulePage::class);
    }

    public function save(PracticalSubmodulePage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmodulePage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return PracticalSubmodulePage[] Returns an array of PracticalSubmodulePage objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PracticalSubmodulePage
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

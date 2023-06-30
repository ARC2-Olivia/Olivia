<?php

namespace App\Repository;

use App\Entity\PracticalSubmoduleProcessorHtml;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleProcessorHtml>
 *
 * @method PracticalSubmoduleProcessorHtml|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleProcessorHtml|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleProcessorHtml[]    findAll()
 * @method PracticalSubmoduleProcessorHtml[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleProcessorHtmlRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleProcessorHtml::class);
    }

    public function save(PracticalSubmoduleProcessorHtml $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleProcessorHtml $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return PracticalSubmoduleProcessorHtml[] Returns an array of PracticalSubmoduleProcessorHtml objects
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

//    public function findOneBySomeField($value): ?PracticalSubmoduleProcessorHtml
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

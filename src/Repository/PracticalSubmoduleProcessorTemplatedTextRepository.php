<?php

namespace App\Repository;

use App\Entity\PracticalSubmoduleProcessorTemplatedText;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleProcessorTemplatedText>
 *
 * @method PracticalSubmoduleProcessorTemplatedText|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleProcessorTemplatedText|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleProcessorTemplatedText[]    findAll()
 * @method PracticalSubmoduleProcessorTemplatedText[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleProcessorTemplatedTextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleProcessorTemplatedText::class);
    }

    public function save(PracticalSubmoduleProcessorTemplatedText $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleProcessorTemplatedText $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return PracticalSubmoduleProcessorTemplatedText[] Returns an array of PracticalSubmoduleProcessorTemplatedText objects
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

//    public function findOneBySomeField($value): ?PracticalSubmoduleProcessorTemplatedText
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

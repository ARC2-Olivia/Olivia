<?php

namespace App\Repository;

use App\Entity\Texts;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Texts>
 *
 * @method Texts|null find($id, $lockMode = null, $lockVersion = null)
 * @method Texts|null findOneBy(array $criteria, array $orderBy = null)
 * @method Texts[]    findAll()
 * @method Texts[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TextsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Texts::class);
    }

    public function save(Texts $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Texts $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function get(): ?Texts
    {
        $texts = $this->createQueryBuilder('t')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult()
        ;

        if (null === $texts) {
            $texts = new Texts();
            $this->save($texts, true);
        }

        return $texts;
    }

}

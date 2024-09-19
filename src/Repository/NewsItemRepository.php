<?php

namespace App\Repository;

use App\Entity\NewsItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsItem>
 *
 * @method NewsItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method NewsItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method NewsItem[]    findAll()
 * @method NewsItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsItem::class);
    }

    public function save(NewsItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NewsItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return NewsItem[]
     */
    public function findAllDescending(): array
    {
        return $this->createQueryBuilder('ni')
            ->orderBy('ni.createdAt', 'DESC')
            ->getQuery()->getResult();
    }
}

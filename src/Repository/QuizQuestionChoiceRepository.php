<?php

namespace App\Repository;

use App\Entity\QuizQuestionChoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizQuestionChoice>
 *
 * @method QuizQuestionChoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuizQuestionChoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuizQuestionChoice[]    findAll()
 * @method QuizQuestionChoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuizQuestionChoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizQuestionChoice::class);
    }

    public function save(QuizQuestionChoice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(QuizQuestionChoice $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return QuizQuestionChoice[] */
    public function findCorrectSiblings(QuizQuestionChoice $qqc)
    {
        return $this->createQueryBuilder('qqc')
            ->where('qqc.quizQuestion = :parent')->andWhere('qqc != :child')->andWhere('qqc.correct = :correct')
            ->setParameters(['parent' => $qqc->getQuizQuestion(), 'child' => $qqc, 'correct' => true])
            ->getQuery()->getResult()
        ;
    }
}

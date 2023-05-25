<?php

namespace App\Repository;

use App\Entity\PracticalSubmoduleQuestion;
use App\Entity\PracticalSubmoduleQuestionAnswer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Translatable\Query\TreeWalker\TranslationWalker;
use Gedmo\Translatable\TranslatableListener;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleQuestionAnswer>
 *
 * @method PracticalSubmoduleQuestionAnswer|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleQuestionAnswer|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleQuestionAnswer[]    findAll()
 * @method PracticalSubmoduleQuestionAnswer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleQuestionAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleQuestionAnswer::class);
    }

    public function save(PracticalSubmoduleQuestionAnswer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleQuestionAnswer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByIdForLocale(int $id, string $locale): PracticalSubmoduleQuestionAnswer|null
    {
        $query = $this->createQueryBuilder('psqa')
            ->where('psqa.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
        ;
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, TranslationWalker::class);
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);
        return $query->getOneOrNullResult();
    }

    public function getMaxAnswerValueForQuestion(PracticalSubmoduleQuestion $practicalSubmoduleQuestion): int
    {
        return $this->createQueryBuilder('psqa')
            ->select('COALESCE(MAX(psqa.answerValue), 0)')
            ->where('psqa.practicalSubmoduleQuestion = :question')
            ->setParameter('question', $practicalSubmoduleQuestion)
            ->getQuery()->getSingleScalarResult()
        ;
    }
}

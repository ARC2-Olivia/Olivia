<?php

namespace App\Repository;

use App\Entity\QuizQuestionAnswer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuizQuestionAnswer>
 *
 * @method QuizQuestionAnswer|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuizQuestionAnswer|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuizQuestionAnswer[]    findAll()
 * @method QuizQuestionAnswer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuizQuestionAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuizQuestionAnswer::class);
    }

    public function save(QuizQuestionAnswer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(QuizQuestionAnswer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function dumpForDataAccess(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();
        try {
            $stmt = $conn->prepare('SELECT qqa.id, qqa.question_id, qq.text as question_text, qqa.answer FROM quiz_question_answer qqa LEFT JOIN quiz_question qq ON qqa.question_id = qq.id WHERE qqa.user_id = :userId');
            $result = $stmt->executeQuery(['userId' => $user->getId()]);
            return $result->fetchAllAssociative();
        } catch (Exception $e) {
            return [];
        }
    }
}

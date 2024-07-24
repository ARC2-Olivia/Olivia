<?php

namespace App\Repository;

use App\Entity\PracticalSubmoduleAssessmentAnswer;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PracticalSubmoduleAssessmentAnswer>
 *
 * @method PracticalSubmoduleAssessmentAnswer|null find($id, $lockMode = null, $lockVersion = null)
 * @method PracticalSubmoduleAssessmentAnswer|null findOneBy(array $criteria, array $orderBy = null)
 * @method PracticalSubmoduleAssessmentAnswer[]    findAll()
 * @method PracticalSubmoduleAssessmentAnswer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PracticalSubmoduleAssessmentAnswerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PracticalSubmoduleAssessmentAnswer::class);
    }

    public function save(PracticalSubmoduleAssessmentAnswer $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PracticalSubmoduleAssessmentAnswer $entity, bool $flush = false): void
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
            $stmt = $conn->prepare('SELECT psaa.id, psq.id as practical_submodule_question_id, psq.question_text as practical_submodule_question_text, psqa.id as practical_submodule_question_answer_id, psqa.answer_text as practical_submodule_question_answer_text, psqa.answer_value as practical_submodule_question_answer_value, psaa.answer_value FROM practical_submodule_assessment_answer psaa LEFT JOIN practical_submodule_assessment psa ON psaa.practical_submodule_assessment_id = psa.id LEFT JOIN practical_submodule_question psq ON psaa.practical_submodule_question_id = psq.id LEFT JOIN practical_submodule_question_answer psqa ON psaa.practical_submodule_question_answer_id = psqa.id WHERE psa.user_id = :userId');
            $result = $stmt->executeQuery(['userId' => $user->getId()]);
            return $result->fetchAllAssociative();
        } catch (Exception $e) {
            return [];
        }
    }
}

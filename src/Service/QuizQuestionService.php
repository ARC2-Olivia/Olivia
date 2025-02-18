<?php

namespace App\Service;

use App\Entity\QuizQuestionChoice;
use Doctrine\ORM\EntityManagerInterface;

class QuizQuestionService
{
    private ?EntityManagerInterface $em = null;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function resolveOnlyOneChoiceCorrect(QuizQuestionChoice $qqc): bool
    {
        $changed = false;

        if ($qqc->isCorrect()) {
            $siblings = $this->em->getRepository(QuizQuestionChoice::class)->findCorrectSiblings($qqc);
            foreach ($siblings as $sibling) {
                $sibling->setCorrect(false);
                $changed = true;
            }
        }

        if ($changed) {
            $this->em->flush();
        }

        return $changed;
    }
}
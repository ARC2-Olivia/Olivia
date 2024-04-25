<?php

namespace App\Twig\Runtime;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class PracticalSubmoduleRuntime implements RuntimeExtensionInterface
{
    private ?EntityManagerInterface $em = null;
    private ?Security $security = null;
    private ?TranslatorInterface $translator = null;

    public function __construct(EntityManagerInterface $em, Security $security, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->security = $security;
        $this->translator = $translator;
    }

    public function isAssessmentCompleted(PracticalSubmodule $practicalSubmodule): bool
    {
        if (null === $this->security->getUser()) return false;
        $assessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->security->getUser()]);
        return null !== $assessment && $assessment->isCompleted();
    }

    public function getTotalQuestionsStatistic(PracticalSubmodule $practicalSubmodule): string
    {
        return $this->translator->trans('practicalSubmodule.extra.questionCount', ['%number%' => $this->em->getRepository(PracticalSubmoduleQuestion::class)->countActualQuestions($practicalSubmodule)], 'app');
    }
}
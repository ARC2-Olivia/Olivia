<?php

namespace App\Twig\Runtime;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Extension\RuntimeExtensionInterface;

class PracticalSubmoduleRuntime implements RuntimeExtensionInterface
{
    private ?EntityManagerInterface $em = null;
    private ?Security $security = null;

    public function __construct(EntityManagerInterface $em, Security $security)
    {
        $this->em = $em;
        $this->security = $security;
    }

    public function isAssessmentCompleted(PracticalSubmodule $practicalSubmodule): bool
    {
        if (null === $this->security->getUser()) return false;
        $assessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->security->getUser()]);
        return null !== $assessment && $assessment->isCompleted();
    }
}
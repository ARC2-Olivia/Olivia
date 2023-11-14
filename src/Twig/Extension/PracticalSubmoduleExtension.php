<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\PracticalSubmoduleRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class PracticalSubmoduleExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('assessment_completed', [PracticalSubmoduleRuntime::class, 'isAssessmentCompleted']),
            new TwigFilter('total_questions_statistic', [PracticalSubmoduleRuntime::class, 'getTotalQuestionsStatistic'])
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('assessment_completed', [PracticalSubmoduleRuntime::class, 'isAssessmentCompleted']),
            new TwigFunction('total_questions_statistic', [PracticalSubmoduleRuntime::class, 'getTotalQuestionsStatistic'])
        ];
    }
}
<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\QuizQuestionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class QuizQuestionExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('display_correct_answer', [QuizQuestionRuntime::class, 'displayCorrectAnswer']),
        ];
    }
}

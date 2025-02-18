<?php

namespace App\Twig\Runtime;

use App\Entity\QuizQuestion;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class QuizQuestionRuntime implements RuntimeExtensionInterface
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function displayCorrectAnswer(QuizQuestion $quizQuestion): string
    {
        $text = '';

        if (QuizQuestion::TYPE_TRUE_FALSE === $quizQuestion->getType()) {
            $text = match ($quizQuestion->getCorrectAnswer()) {
                true => $this->translator->trans('common.trueValue', domain: 'app'),
                false => $this->translator->trans('common.falseValue', domain: 'app'),
                default => ''
            };
        } else {
            foreach ($quizQuestion->getQuizQuestionChoices() as $qqc) {
                if ($qqc->isCorrect()) {
                    $text = $qqc->getText();
                    break;
                }
            }
        }

        return $text;
    }
}
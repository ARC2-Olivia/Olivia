<?php

namespace App\Service;

use App\Entity\Evaluation;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class NavigationService
{
    public const EVALUATION_OVERVIEW             = 0;
    public const EVALUATION_EVALUATE             = 1;
    public const EVALUATION_EDIT                 = 2;
    public const EVALUATION_EXTRA_NEW_QUESTION   = 3;
    public const EVALUATION_EXTRA_EDIT_QUESTION  = 4;
    public const EVALUATION_EXTRA_NEW_ANSWER     = 5;
    public const EVALUATION_EXTRA_EDIT_ANSWER    = 6;
    public const EVALUATION_EXTRA_NEW_EVALUATOR  = 7;
    public const EVALUATION_EXTRA_EDIT_EVALUATOR = 8;

    private ?TranslatorInterface $translator = null;
    private ?RouterInterface $router = null;
    private ?Security $security = null;

    public function __construct(TranslatorInterface $translator, RouterInterface $router, Security $security)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->security = $security;
    }

    public function forEvaluation(Evaluation $evaluation, ?int $activeNav = null): array
    {
        $navigation = [
            [
                'text' => $this->translator->trans('evaluation.nav.overview', [], 'app'),
                'path' => $this->router->generate('evaluation_overview', ['evaluation' => $evaluation->getId()]),
                'active' => $activeNav === self::EVALUATION_OVERVIEW
            ]
        ];

        if ($this->security->isGranted('ROLE_USER')) {
            $navigation[] = [
                'text' => $this->translator->trans('evaluation.nav.evaluate', [], 'app'),
                'path' => $this->router->generate('evaluation_evaluate', ['evaluation' => $evaluation->getId()]),
                'active' => $activeNav === self::EVALUATION_EVALUATE
            ];
        }

        if ($this->security->isGranted('ROLE_MODERATOR')) {
            $navigation[] = [
                'text' => $this->translator->trans('evaluation.nav.edit.default', [], 'app'),
                'path' => $this->router->generate('evaluation_edit', ['evaluation' => $evaluation->getId()]),
                'active' => $activeNav === self::EVALUATION_EDIT
            ];

            switch ($activeNav) {
                case self::EVALUATION_EXTRA_NEW_QUESTION: $navigation[]   = ['text' => $this->translator->trans('evaluation.nav.new.question', [], 'app'), 'active' => true];   break;
                case self::EVALUATION_EXTRA_EDIT_QUESTION: $navigation[]  = ['text' => $this->translator->trans('evaluation.nav.edit.question', [], 'app'), 'active' => true];  break;
                case self::EVALUATION_EXTRA_NEW_ANSWER: $navigation[]     = ['text' => $this->translator->trans('evaluation.nav.new.answer', [], 'app'), 'active' => true];     break;
                case self::EVALUATION_EXTRA_EDIT_ANSWER: $navigation[]    = ['text' => $this->translator->trans('evaluation.nav.edit.answer', [], 'app'), 'active' => true];    break;
                case self::EVALUATION_EXTRA_NEW_EVALUATOR: $navigation[]  = ['text' => $this->translator->trans('evaluation.nav.new.evaluator', [], 'app'), 'active' => true];  break;
                case self::EVALUATION_EXTRA_EDIT_EVALUATOR: $navigation[] = ['text' => $this->translator->trans('evaluation.nav.edit.evaluator', [], 'app'), 'active' => true]; break;
            }
        }

        return $navigation;
    }
}
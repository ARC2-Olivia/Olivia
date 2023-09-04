<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\PracticalSubmodule;
use App\Entity\User;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class NavigationService
{
    public const COURSE_OVERVIEW    = 0;
    public const COURSE_LESSONS     = 1;
    public const COURSE_EDIT        = 2;
    public const COURSE_CERTIFICATE = 3;

    public const EVALUATION_OVERVIEW             = 0;
    public const EVALUATION_EVALUATE             = 1;
    public const EVALUATION_EDIT                 = 2;
    public const EVALUATION_EXTRA_RESULTS        = 3;
    public const EVALUATION_EXTRA_NEW_QUESTION   = 4;
    public const EVALUATION_EXTRA_EDIT_QUESTION  = 5;
    public const EVALUATION_EXTRA_NEW_ANSWER     = 6;
    public const EVALUATION_EXTRA_EDIT_ANSWER    = 7;
    public const EVALUATION_EXTRA_NEW_EVALUATOR  = 8;
    public const EVALUATION_EXTRA_EDIT_EVALUATOR = 9;
    public const EVALUATION_EXTRA_NEW_PAGE       = 10;
    public const EVALUATION_EXTRA_EDIT_PAGE      = 11;

    private ?TranslatorInterface $translator = null;
    private ?RouterInterface $router = null;
    private ?Security $security = null;
    private ?EnrollmentService $enrollmentService = null;
    private ?\Twig\Environment $twig;

    public function __construct(TranslatorInterface $translator, RouterInterface $router, Security $security, EnrollmentService $enrollmentService, \Twig\Environment $twig)
    {
        $this->translator = $translator;
        $this->router = $router;
        $this->security = $security;
        $this->enrollmentService = $enrollmentService;
        $this->twig = $twig;
    }

    public function forCourse(Course $course, ?int $activeNav = null): array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        $navigation = [
            [
                'text' => $this->translator->trans('course.nav.overview', [], 'app'),
                'path' => $this->router->generate('course_overview', ['course' => $course->getId()]),
                'active' => $activeNav === self::COURSE_OVERVIEW
            ],
            [
                'text' => $this->translator->trans('course.nav.lessons', [], 'app'),
                'path' => $this->router->generate('lesson_course', ['course' => $course->getId()]),
                'active' => $activeNav === self::COURSE_LESSONS
            ]
        ];

        if ($this->isUser() && $this->enrollmentService->isEnrolled($course, $user)) {
            $addition = '';
            if ($this->enrollmentService->isPassed($course, $user)) {
                $addition .= $this->twig->render('mdi/check-decagram.html.twig', ['class' => 'ms-1 fg-orange', 'viewBox' => '0 0 24 24']);
            }
            $navigation[] = [
                'text' => $this->translator->trans('course.nav.certificate', [], 'app') . $addition,
                'path' => $this->router->generate('course_certificate', ['course' => $course->getId()]),
                'active' => $activeNav === self::COURSE_CERTIFICATE
            ];
        }

        return $navigation;
    }

    public function forPracticalSubmodule(PracticalSubmodule $practicalSubmodule, ?int $activeNav = null): array
    {
        $navigation = [
            [
                'text' => $this->translator->trans('practicalSubmodule.nav.overview', [], 'app'),
                'path' => $this->router->generate('practical_submodule_overview', ['practicalSubmodule' => $practicalSubmodule->getId()]),
                'active' => $activeNav === self::EVALUATION_OVERVIEW
            ]
        ];

        if ($this->security->isGranted('ROLE_USER')) {
            $navigation[] = [
                'text' => $this->translator->trans('practicalSubmodule.nav.evaluate', [], 'app'),
                'path' => $this->router->generate('practical_submodule_evaluate', ['practicalSubmodule' => $practicalSubmodule->getId()]),
                'active' => $activeNav === self::EVALUATION_EVALUATE
            ];

            if ($activeNav === self::EVALUATION_EXTRA_RESULTS) $navigation[] = ['text' => $this->translator->trans('practicalSubmodule.nav.results', [], 'app'), 'active' => true];
        }

        if ($this->security->isGranted('ROLE_MODERATOR')) {
            $navigation[] = [
                'text' => $this->translator->trans('practicalSubmodule.nav.edit.default', [], 'app'),
                'path' => $this->router->generate('practical_submodule_edit', ['practicalSubmodule' => $practicalSubmodule->getId()]),
                'active' => $activeNav === self::EVALUATION_EDIT
            ];

            switch ($activeNav) {
                case self::EVALUATION_EXTRA_NEW_QUESTION:   $navigation[] = ['text' => $this->translator->trans('practicalSubmodule.nav.new.question', [], 'app'), 'active' => true];   break;
                case self::EVALUATION_EXTRA_EDIT_QUESTION:  $navigation[] = ['text' => $this->translator->trans('practicalSubmodule.nav.edit.question', [], 'app'), 'active' => true];  break;
                case self::EVALUATION_EXTRA_NEW_ANSWER:     $navigation[] = ['text' => $this->translator->trans('practicalSubmodule.nav.new.answer', [], 'app'), 'active' => true];     break;
                case self::EVALUATION_EXTRA_EDIT_ANSWER:    $navigation[] = ['text' => $this->translator->trans('practicalSubmodule.nav.edit.answer', [], 'app'), 'active' => true];    break;
                case self::EVALUATION_EXTRA_NEW_EVALUATOR:  $navigation[] = ['text' => $this->translator->trans('practicalSubmodule.nav.new.evaluator', [], 'app'), 'active' => true];  break;
                case self::EVALUATION_EXTRA_EDIT_EVALUATOR: $navigation[] = ['text' => $this->translator->trans('practicalSubmodule.nav.edit.evaluator', [], 'app'), 'active' => true]; break;
                case self::EVALUATION_EXTRA_NEW_PAGE:       $navigation[] = ['text' => $this->translator->trans('practicalSubmodule.nav.new.page', [], 'app'), 'active' => true];       break;
                case self::EVALUATION_EXTRA_EDIT_PAGE:      $navigation[] = ['text' => $this->translator->trans('practicalSubmodule.nav.edit.page', [], 'app'), 'active' => true];      break;
            }
        }

        return $navigation;
    }

    private function isUser(): bool
    {
        return $this->security->isGranted('ROLE_USER')
            && !$this->security->isGranted('ROLE_MODERATOR')
            && !$this->security->isGranted('ROLE_ADMIN')
        ;
    }
}
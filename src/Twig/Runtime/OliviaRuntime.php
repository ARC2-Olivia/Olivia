<?php

namespace App\Twig\Runtime;

use App\Entity\Course;
use App\Entity\LessonItemEmbeddedVideo;
use App\Entity\PracticalSubmoduleProcessorImplementationInterface;
use App\Entity\User;
use App\Service\EnrollmentService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class OliviaRuntime implements RuntimeExtensionInterface
{
    const TEMPLATE_YOUTUBE_EMBED_URL = 'https://www.youtube.com/embed/%s';

    private ?TranslatorInterface $translator = null;
    private ?EnrollmentService $enrollmentService = null;
    private ?Security $security = null;
    private ?ParameterBagInterface $parameterBag = null;
    private ?ValidatorInterface $validator = null;

    public function __construct(TranslatorInterface $translator,
                                EnrollmentService $enrollmentService,
                                Security $security,
                                ParameterBagInterface $parameterBag,
                                ValidatorInterface $validator
    )
    {
        $this->translator = $translator;
        $this->enrollmentService = $enrollmentService;
        $this->security = $security;
        $this->parameterBag = $parameterBag;
        $this->validator = $validator;
    }

    public function translateWorkload(Course $course, bool $forceDefaultLocale = false): string
    {
        $workload = $course->getEstimatedWorkload();
        if (!empty($workload)) {
            list($value, $time) = explode(' ', $workload);
            if (true === $forceDefaultLocale) {
                $locale = $this->parameterBag->get('locale.default');
                switch ($time) {
                    case 'H': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.hours', [], 'app', $locale);
                    case 'D': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.days', [], 'app', $locale);
                    case 'W': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.weeks', [], 'app', $locale);
                    case 'M': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.months', [], 'app', $locale);
                    case 'Y': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.years', [], 'app', $locale);
                }
            } else {
                switch ($time) {
                    case 'H': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.hours', [], 'app');
                    case 'D': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.days', [], 'app');
                    case 'W': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.weeks', [], 'app');
                    case 'M': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.months', [], 'app');
                    case 'Y': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.years', [], 'app');
                }
            }
        }
        return '';
    }

    public function getYoutubeEmbedLink(LessonItemEmbeddedVideo $lessonItem): ?string
    {
        $videoUrl = $lessonItem->getVideoUrl();

        if (str_contains($videoUrl, 'youtube')) {
            // Get YouTube video ID.
            $explodedUrl = explode('v=', $videoUrl);
            if (count($explodedUrl) < 2) {
                return null;
            }
            $ytVideoId = $explodedUrl[1];

            // If URL had other parameters, remove them.
            $ampersandPosition = strpos('&', $ytVideoId);
            if ($ampersandPosition !== false) {
                $ytVideoId = substr($ytVideoId, 0, $ampersandPosition);
            }

            // Return YouTube embed URL.
            return sprintf(self::TEMPLATE_YOUTUBE_EMBED_URL, $ytVideoId);
        }

        if (str_contains($videoUrl, 'youtu.be')) {
            // Get YouTube video ID.
            $explodedUrl = explode('/', $videoUrl);
            $explodedUrlCount = count($explodedUrl);
            if ($explodedUrlCount === 0) {
                return null;
            }
            $ytVideoId = $explodedUrl[$explodedUrlCount - 1];

            // If URL had other parameters, remove them.
            $ampersandPosition = strpos('&', $ytVideoId);
            if ($ampersandPosition !== false) {
                $ytVideoId = substr($ytVideoId, 0, $ampersandPosition);
            }

            // Return YouTube embed URL.
            return sprintf(self::TEMPLATE_YOUTUBE_EMBED_URL, $ytVideoId);
        }

        return null;
    }

    public function isValidEvaluator(?PracticalSubmoduleProcessorImplementationInterface $processorImpl)
    {
        if ($processorImpl !== null) {
            $errors = $this->validator->validate($processorImpl);
            return $errors->count() === 0;
        }
        return false;
    }

    public function isEnrolled(Course $course, ?User $user): bool
    {
        return null !== $user && $this->enrollmentService->isEnrolled($course, $user);
    }

    public function isUser(): bool
    {
        return $this->security->isGranted('ROLE_USER')
            && !$this->security->isGranted('ROLE_MODERATOR')
            && !$this->security->isGranted('ROLE_ADMIN');
    }

    public function isPassed(Course $course, ?User $user): bool
    {
        return null !== $user && $this->enrollmentService->isPassed($course, $user);
    }

    public function textToHtml(?string $text): \Twig\Markup
    {
        $charset = 'UTF-8';
        if (null === $text || '' === trim($text)) {
            return new \Twig\Markup('', $charset);
        }

        $text = explode("\n", $text);
        foreach ($text as &$line) {
            if (str_starts_with($line, '-')) {
                $line = preg_replace('/^-\s*/', '', $line);
                $line = "<p class='fake-li'>$line</p>";
            } else {
                $line = "<p>$line</p>";
            }
        }

        return new \Twig\Markup(implode('', $text), $charset);
    }

    public function makePairs(array $array): \Iterator
    {
        $length = count($array);
        $i = 0;
        while ($i < $length) {
            $j = $i + 1;
            if ($j < $length) {
                yield [$array[$i], $array[$j]];
            } else {
                yield [$array[$i]];
            }
            $i += 2;
        }
    }
}
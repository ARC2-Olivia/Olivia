<?php

namespace App\Twig;

use App\Entity\Course;
use App\Entity\LessonItemEmbeddedVideo;
use App\Entity\User;
use App\Security\EnrollmentService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class OliviaExtension extends AbstractExtension
{
    const TEMPLATE_YOUTUBE_EMBED_URL = 'https://www.youtube.com/embed/%s';

    private ?TranslatorInterface $translator = null;
    private ?EnrollmentService $enrollmentService = null;

    public function __construct(TranslatorInterface $translator, EnrollmentService $enrollmentService)
    {
        $this->translator = $translator;
        $this->enrollmentService = $enrollmentService;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('translate_workload', [$this, 'translateWorkload']),
            new TwigFilter('youtube_embed_link', [$this, 'getYoutubeEmbedLink']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('is_enrolled', [$this, 'isEnrolled'])
        ];
    }

    public function translateWorkload(Course $course): string
    {
        $workload = $course->getEstimatedWorkload();
        if (!empty($workload)) {
            list($value, $time) = explode(' ', $workload);
            switch ($time) {
                case 'H': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.hours', [], 'app');
                case 'D': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.days', [], 'app');
                case 'W': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.weeks', [], 'app');
                case 'M': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.months', [], 'app');
                case 'Y': return $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.years', [], 'app');
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

    public function isEnrolled(Course $course, User $user): bool
    {
        return $this->enrollmentService->isEnrolled($course, $user);
    }
}
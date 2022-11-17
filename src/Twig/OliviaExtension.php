<?php

namespace App\Twig;

use App\Entity\Course;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class OliviaExtension extends AbstractExtension
{
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('translate_workload', [$this, 'translateWorkload'])
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
}
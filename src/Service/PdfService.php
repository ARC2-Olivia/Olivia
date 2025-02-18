<?php

namespace App\Service;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PdfService
{
    private ?PracticalSubmoduleService $practicalSubmoduleService = null;
    private ?WkhtmltopdfService $wkhtmltopdfService = null;
    private ?\Twig\Environment $twig = null;

    public function __construct(PracticalSubmoduleService $practicalSubmoduleService, WkhtmltopdfService $wkhtmltopdfService, \Twig\Environment $twig)
    {
        $this->practicalSubmoduleService = $practicalSubmoduleService;
        $this->wkhtmltopdfService = $wkhtmltopdfService;
        $this->twig = $twig;
    }

    public function generateDocumentFromAssessment(PracticalSubmoduleAssessment $assessment, string $locale)
    {
        return match ($assessment->getPracticalSubmodule()->getExportType()) {
            PracticalSubmodule::EXPORT_TYPE_VIDEO_SURVEILLANCE_NOTIFICATION => $this->generateVideoSurveillanceNotification($assessment, $locale),
            default => null
        };
    }

    private function generateVideoSurveillanceNotification(PracticalSubmoduleAssessment $assessment, string $locale)
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $parameters = [];
        foreach ($results as $result)
            $parameters[$result->getExportTag()] = $result->getText();
        return $this->wkhtmltopdfService->makePortraitPdf($this->twig->render("pdf/result/$locale/ps_export_template_vsn.html.twig", $parameters));
    }
}
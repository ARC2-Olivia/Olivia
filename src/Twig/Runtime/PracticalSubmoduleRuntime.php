<?php

namespace App\Twig\Runtime;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\PracticalSubmoduleQuestion;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

class PracticalSubmoduleRuntime implements RuntimeExtensionInterface
{
    private ?EntityManagerInterface $em = null;
    private ?Security $security = null;
    private ?TranslatorInterface $translator = null;

    public function __construct(EntityManagerInterface $em, Security $security, TranslatorInterface $translator)
    {
        $this->em = $em;
        $this->security = $security;
        $this->translator = $translator;
    }

    public function isAssessmentCompleted(PracticalSubmodule $practicalSubmodule): bool
    {
        if (null === $this->security->getUser()) return false;
        $assessment = $this->em->getRepository(PracticalSubmoduleAssessment::class)->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->security->getUser()]);
        return null !== $assessment && $assessment->isCompleted();
    }

    public function getTotalQuestionsStatistic(PracticalSubmodule $practicalSubmodule): string
    {
        return $this->translator->trans('practicalSubmodule.extra.questionCount', ['%number%' => $this->em->getRepository(PracticalSubmoduleQuestion::class)->countActualQuestions($practicalSubmodule)], 'app');
    }

    public function getExportButtonText(PracticalSubmodule $practicalSubmodule): string
    {
        switch ($practicalSubmodule->getExportType()) {
            case PracticalSubmodule::EXPORT_TYPE_RESPONDENTS_RIGHTS:
                return $this->translator->trans('practicalSubmodule.exportButtonText.respondentsRights', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_PERSONAL_DATA_PROCESSING_CONSENT:
                return $this->translator->trans('practicalSubmodule.exportButtonText.consentPersonalDataProcessing', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_LIA:
                return $this->translator->trans('practicalSubmodule.exportButtonText.lia', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_PRIVACY_POLICY:
                return $this->translator->trans('practicalSubmodule.exportButtonText.privacyPolicy', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_CONTROLLER_PROCESSOR_CONTRACT:
                return $this->translator->trans('practicalSubmodule.exportButtonText.controllerProcessorContract', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_RECORDS_OF_PROCESSING_ACTIVITIES_DC:
            case PracticalSubmodule::EXPORT_TYPE_RECORDS_OF_PROCESSING_ACTIVITIES_DP:
                return $this->translator->trans('practicalSubmodule.exportButtonText.recordsOfProcessingActivities', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_RULEBOOK_ON_ISS:
            case PracticalSubmodule::EXPORT_TYPE_RULEBOOK_ON_PDP:
                return $this->translator->trans('practicalSubmodule.exportButtonText.rulebookOnMeasures', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_DPIA:
                return $this->translator->trans('practicalSubmodule.exportButtonText.dpia', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_COOKIE_POLICY:
                return $this->translator->trans('practicalSubmodule.exportButtonText.cookiePolicy', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_VIDEO_SURVEILLANCE_NOTIFICATION:
                return $this->translator->trans('practicalSubmodule.exportButtonText.videoSurveillanceNotification', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_VIDEO_SURVEILLANCE_RULEBOOK:
                return $this->translator->trans('practicalSubmodule.exportButtonText.videoSurveillanceRulebook', domain: 'app');
            case PracticalSubmodule::EXPORT_TYPE_TIA:
                return $this->translator->trans('practicalSubmodule.exportButtonText.tia', domain: 'app');
        }
        return $this->translator->trans('button.export', domain: 'app');
    }
}
<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\File;
use App\Entity\Instructor;
use App\Entity\LessonItemFile;
use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\Topic;
use App\Entity\User;
use App\Repository\CourseRepository;
use App\Repository\PracticalSubmoduleAssessmentRepository;
use App\Repository\PracticalSubmoduleQuestionRepository;
use App\Service\PracticalSubmoduleService;
use App\Service\WkhtmltopdfService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route("/file-fetch", name: "file_fetch_")]
class FileFetchController extends AbstractController
{
    #[Route("/course-image/{course}", name: 'course_image')]
    public function courseImage(Course $course): Response
    {
        $dir = $this->getParameter('dir.course_image');
        $filepath = $dir . '/' . $course->getImage();
        return $this->file($filepath);
    }

    #[Route("/practical-submodule-image/{practicalSubmodule}", name: 'practical_submodule_image')]
    public function practicalSubmoduleImage(PracticalSubmodule $practicalSubmodule): Response
    {
        $dir = $this->getParameter('dir.practical_submodule_image');
        $filepath = $dir . '/' . $practicalSubmodule->getImage();
        return $this->file($filepath);
    }

    #[Route("/topic-image/{topic}", name: 'topic_image')]
    public function topicImage(Topic $topic): Response
    {
        $dir = $this->getParameter('dir.topic_image');
        $filepath = $dir . '/' . $topic->getImage();
        return $this->file($filepath);
    }

    #[Route("/instructor-image/{instructor}", name: "instructor_image")]
    public function instructorImage(Instructor $instructor): Response
    {
        $dir = $this->getParameter('dir.instructor_image');
        $filepath = $dir . '/' . $instructor->getImage();
        return $this->file($filepath);
    }

    #[Route("/lesson-file/{lessonItemFile}", name: 'lesson_file')]
    public function lessonFile(LessonItemFile $lessonItemFile): Response
    {
        $dir = $this->getParameter('dir.lesson_file');
        $filepath = $dir . '/' . $lessonItemFile->getFilename();
        return $this->file($filepath);
    }

    #[Route("/uploaded-file/{file}", name: "uploaded_file")]
    public function uploadedFile(File $file): Response
    {
        return $this->file($file->getPath(), $file->getOriginalName());
    }

    #[Route("/course-certificate/{course}/{_locale}", name: "course_certificate", requirements: ["_locale" => "%locale.supported%"])]
    #[IsGranted('get_certificate', subject: 'course')]
    public function courseCertificate(Course $course,
                                      CourseRepository $courseRepository,
                                      WkhtmltopdfService $wkhtmltopdfService,
                                      \Twig\Environment $twig,
                                      RouterInterface $router,
                                      TranslatorInterface $translator,
                                      Request $request
    ): Response
    {
        $defaultLocale = $this->getParameter('locale.default');
        $alternateLocale = $this->getParameter('locale.alternate');
        $locales = [$defaultLocale, $alternateLocale];

        /** @var User $user */ $user = $this->getUser();
        $projectDir = $this->getParameter('kernel.project_dir');
        $strings = [];
        foreach ($locales as $locale) {
            $course = $courseRepository->findByIdForLocale($course->getId(), $locale);
            $strings[$locale] = [
                'title' => $translator->trans('certificate.title', domain: 'app', locale: $locale),
                'subtitle' => $translator->trans('certificate.subtitle', domain: 'app', locale: $locale),
                'for' => $translator->trans('certificate.for', domain: 'app', locale: $locale),
                'workload' => $translator->trans('certificate.workload', domain: 'app', locale: $locale),
                'learningOutcomes' => $translator->trans('certificate.learningOutcomes', domain: 'app', locale: $locale),
                'issueDate' => $translator->trans('certificate.issueDate', domain: 'app', locale: $locale),
                'certifiedBy' => $translator->trans('certificate.certifiedBy', domain: 'app', locale: $locale),
                'url' => $translator->trans('certificate.url', domain: 'app', locale: $locale),
                'course' => [
                    'name' => $course->getName(),
                    'workload' => $this->translateWorkload($course, $translator, $locale),
                    'learningOutcomes' => $course->getLearningOutcomesAsArray(),
                    'link' => str_replace(['http://', 'https://'], '', $router->generate('course_overview', ['course' => $course->getId(), '_locale' => $locale], UrlGeneratorInterface::ABSOLUTE_URL))
                ]
            ];
        }

        $html = $twig->render('pdf/certificate.html.twig', ['user' => $user, 'projectDir' => $projectDir, 'defaultLocale' => $defaultLocale, 'alternateLocale' => $alternateLocale, 'strings' => $strings]);
        $pdf = $wkhtmltopdfService->makePortraitPdf($html);

        if ($request->getLocale() !== $this->getParameter('locale.default')) {
            $course = $courseRepository->findByIdForLocale($course->getId(), $this->getParameter('locale.default'));
        }

        $filename = 'OLIVIA-certificate-' . preg_replace('/[^A-Za-z0-9_]/', '', str_replace(' ', '_', $course->getName())) . '.pdf';
        return $this->file($pdf, $filename)->deleteFileAfterSend();
    }

    #[Route("/golden-certificate/{_locale}", name: "golden_certificate", requirements: ["_locale" => "%locale.supported%"])]
    public function allCertificate(CourseRepository $courseRepository,
                                   WkhtmltopdfService $wkhtmltopdfService,
                                   \Twig\Environment $twig,
                                   TranslatorInterface $translator,
                                   Request $request,
    ): Response
    {
        if (null !== $this->getUser()->getAllCoursesPassedAt()) {
            $this->createAccessDeniedException();
        }
        $defaultLocale = $this->getParameter('locale.default');
        $alternateLocale = $this->getParameter('locale.alternate');
        $locales = [$defaultLocale, $alternateLocale];

        /** @var User $user */ $user = $this->getUser();
        $projectDir = $this->getParameter('kernel.project_dir');
        $strings = [];
        foreach ($locales as $locale) {
            $courses = $courseRepository->findAllForLocale($locale);
            $strings[$locale] = [
                'title' => $translator->trans('certificate.title', domain: 'app', locale: $locale),
                'subtitle' => $translator->trans('certificate.subtitle', domain: 'app', locale: $locale),
                'for' => $translator->trans('certificate.for', domain: 'app', locale: $locale),
                'reason' => $translator->trans('certificate.allCourses', domain: 'app', locale: $locale),
                'completedCourses' => $translator->trans('certificate.completedCourses', domain: 'app', locale: $locale),
                'issueDate' => $translator->trans('certificate.issueDate', domain: 'app', locale: $locale),
                'certifiedBy' => $translator->trans('certificate.certifiedBy', domain: 'app', locale: $locale),
                'courses' => array_map(function (Course $course) { return $course->getNameOrPublicName(); }, $courses)
            ];
        }

        $html = $twig->render('pdf/goldenCertificate.html.twig', ['user' => $user, 'projectDir' => $projectDir, 'defaultLocale' => $defaultLocale, 'alternateLocale' => $alternateLocale, 'strings' => $strings]);
        $pdf = $wkhtmltopdfService->makePortraitPdf($html);

        $filename = 'OLIVIA-certificate.pdf';
        return $this->file($pdf, $filename)->deleteFileAfterSend();
    }

    #[Route("/ps-report-answers/{practicalSubmodule}/{_locale}", name: "practical_submodule_report_answers", requirements: ["_locale" => "%locale.supported%"])]
    public function practicalSubmoduleReportAnswers(PracticalSubmodule $practicalSubmodule,
                                                    WkhtmltopdfService $wkhtmltopdfService,
                                                    PracticalSubmoduleQuestionRepository $practicalSubmoduleQuestionRepository,
                                                    PracticalSubmoduleAssessmentRepository $practicalSubmoduleAssessmentRepository,
                                                    PracticalSubmoduleService $practicalSubmoduleService
    ): Response
    {
        $assessment = $practicalSubmoduleAssessmentRepository->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->getUser()]);
        $answerData = [];
        $pdf = null;

        if (PracticalSubmodule::MODE_OF_OPERATION_SIMPLE === $practicalSubmodule->getModeOfOperation()) {
            $results = $practicalSubmoduleService->runProcessors($assessment);
            foreach ($results as $result) {
                $answerDatum = ['result' => $result, 'answers' => null];
                if ($result->isQuestionSet()) {
                    foreach ($assessment->getPracticalSubmoduleAssessmentAnswers() as $answer) {
                        if ($answer->getPracticalSubmoduleQuestion()->getId() !== $result->getQuestion()->getId()) continue;
                        if (null === $answerDatum['answers']) $answerDatum['answers'] = [];
                        $answerDatum['answers'][] = $answer->getDisplayableAnswer();
                    }
                }
                $answerData[] = $answerDatum;
            }

            $html = $this->renderView('pdf/reportAnswers_simple.html.twig', ['answerData' => $answerData, 'practicalSubmodule' => $practicalSubmodule]);
            $pdf = $wkhtmltopdfService->makeLandscapePdf($html);
        } else {
            if (null !== $assessment) {
                foreach ($assessment->getPracticalSubmoduleAssessmentAnswers() as $answer) {
                    $questionId = $answer->getPracticalSubmoduleQuestion()->getId();
                    if (!key_exists($questionId, $answerData)) {
                        $answerDatum = new \stdClass();
                        $answerDatum->questionId = $questionId;
                        $answerDatum->question = $answer->getPracticalSubmoduleQuestion()->getQuestionText();
                        $answerDatum->answers = [];
                        $answerDatum->dependentQuestionId = $answer->getPracticalSubmoduleQuestion()?->getDependentPracticalSubmoduleQuestion()?->getId();
                        $answerDatum->dependees = [];
                        $answerDatum->unansweredDependees = [];
                        $answerData[$questionId] = $answerDatum;
                    }
                    $answerData[$questionId]->answers[] = $answer->getDisplayableAnswer();
                }
            }

            $dependeeIds = [];
            $dependencyGrouping = [];
            foreach ($answerData as $questionId => $answerDatum) {
                $dependentQuestionId = $answerDatum->dependentQuestionId;
                if (null !== $dependentQuestionId) {
                    if (!key_exists($dependentQuestionId, $dependencyGrouping)) {
                        $dependencyGrouping[$dependentQuestionId] = [];
                    }
                    $dependencyGrouping[$dependentQuestionId][] = $questionId;
                    $dependeeIds[] = $questionId;
                }
            }

            foreach ($dependencyGrouping as $dependentQuestionId => $questionIds) {
                foreach ($questionIds as $questionId) {
                    $answerData[$dependentQuestionId]->dependees[] = $answerData[$questionId];
                }
            }

            foreach ($dependeeIds as $dependeeId) {
                unset($answerData[$dependeeId]);
            }

            foreach (array_keys($answerData) as $questionId) {
                $exclusions = array_map(function ($dependee) { return $dependee->questionId; }, $answerData[$questionId]->dependees);
                $unansweredDependees = $practicalSubmoduleQuestionRepository->findDependingQuestionTexts($questionId, $exclusions);
                $answerData[$questionId]->unansweredDependees = $unansweredDependees;
            }

            $html = $this->renderView('pdf/reportAnswers.html.twig', ['answerData' => $answerData, 'practicalSubmodule' => $practicalSubmodule]);
            $pdf = $wkhtmltopdfService->makePortraitPdf($html);
        }


        if (null === $pdf) return $this->makeFileResponseFromString("report.txt", "Unable to process request.");
        return $this->file($pdf, 'report.pdf')->deleteFileAfterSend();
    }

    #[Route("/consent-form/{practicalSubmodule}", name: "consent_form")]
    public function consentForm(PracticalSubmodule $practicalSubmodule,
                                \Twig\Environment $twig,
                                PracticalSubmoduleService $practicalSubmoduleService,
                                PracticalSubmoduleAssessmentRepository $practicalSubmoduleAssessmentRepository
    ): Response
    {
        if ($practicalSubmodule::EXPORT_TYPE_COOKIE_BANNER !== $practicalSubmodule->getExportType()) {
            return $this->makeFileResponseFromString("report.txt", "Unable to process request.");
        }
        $assessment = $practicalSubmoduleAssessmentRepository->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->getUser()]);
        $results = $practicalSubmoduleService->runProcessors($assessment);

        $cookieBanner = [];
        foreach ($results as $result) {
            if ('cookies' !== $result->getExportTag()) {
                $cookieBanner[$result->getExportTag()] = $result->getText();
                continue;
            }

            $cookies = explode('/*/', $result->getText());
            foreach ($cookies as &$cookie) {
                list ($cookieType, $cookieDescription) = explode('|*|', $cookie);
                $cookie = new \stdClass();
                $cookie->type = $cookieType;
                $cookie->description = $cookieDescription;
            }
            $cookieBanner['cookies'] = $cookies;
        }

        $showBanner = key_exists('link', $cookieBanner) && key_exists('cookies', $cookieBanner);
        $html = $twig->render('export/cookieBanner.html.twig', ['cookieBanner' => $cookieBanner, 'showBanner' => $showBanner]);
        return $this->makeFileResponseFromString('consent-form.html', $html, 'text/html');
    }

    private function makeFileResponseFromString(string $filename, string $content, string $contentType = 'text/plain'): Response
    {
        $response = new Response($content);
        $response->headers->set('Content-Type', $contentType);
        $response->headers->set('Content-Length', strlen($content));
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename));
        return $response;
    }

    private function translateWorkload(Course $course, TranslatorInterface $translator, $locale): string
    {
        $workload = $course->getEstimatedWorkload();
        if (!empty($workload)) {
            list($value, $time) = explode(' ', $workload);
                switch ($time) {
                    case 'H': return $value . ' ' . $translator->trans('form.entity.course.choices.estimatedWorkload.hours', [], 'app', $locale);
                    case 'D': return $value . ' ' . $translator->trans('form.entity.course.choices.estimatedWorkload.days', [], 'app', $locale);
                    case 'W': return $value . ' ' . $translator->trans('form.entity.course.choices.estimatedWorkload.weeks', [], 'app', $locale);
                    case 'M': return $value . ' ' . $translator->trans('form.entity.course.choices.estimatedWorkload.months', [], 'app', $locale);
                    case 'Y': return $value . ' ' . $translator->trans('form.entity.course.choices.estimatedWorkload.years', [], 'app', $locale);
                }
        }
        return '';
    }
}
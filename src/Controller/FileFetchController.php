<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\File;
use App\Entity\Instructor;
use App\Entity\LessonItemFile;
use App\Entity\PracticalSubmodule;
use App\Entity\Topic;
use App\Repository\PracticalSubmoduleAssessmentRepository;
use App\Repository\PracticalSubmoduleQuestionRepository;
use App\Service\PracticalSubmoduleService;
use App\Service\WkhtmltopdfService;
use App\Service\WordService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

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
    public function courseCertificate(Course $course, WordService $wordService): Response
    {
        $document = $wordService->generateCourseCertificateForUser($course, $this->getUser());
        $filename = 'certificate.docx';
        return $this->file($document, $filename)->deleteFileAfterSend();
    }

    #[Route("/ps-report-answers/{practicalSubmodule}/{_locale}", name: "practical_submodule_report_answers", requirements: ["_locale" => "%locale.supported%"])]
    public function practicalSubmoduleReportAnswers(PracticalSubmodule $practicalSubmodule,
                                                    WkhtmltopdfService $wkhtmltopdfService,
                                                    PracticalSubmoduleQuestionRepository $practicalSubmoduleQuestionRepository,
                                                    PracticalSubmoduleAssessmentRepository $practicalSubmoduleAssessmentRepository
    ): Response
    {
        $assessment = $practicalSubmoduleAssessmentRepository->findOneBy(['practicalSubmodule' => $practicalSubmodule, 'user' => $this->getUser()]);
        $answerData = [];

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
            $cookieBanner[$result->getExportTag()] = $result->getText();
        }
        $html = $twig->render('export/cookieBanner.html.twig', ['cookieBanner' => $cookieBanner]);
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
}
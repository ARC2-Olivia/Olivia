<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\User;
use App\Misc\ProcessorResult;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBox;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class WordService
{
    private ?PracticalSubmoduleService $practicalSubmoduleService = null;
    private ?ParameterBagInterface $parameterBag = null;
    private ?TranslatorInterface $translator = null;
    private ?RouterInterface $router = null;

    public function __construct(PracticalSubmoduleService $practicalSubmoduleService, ParameterBagInterface $parameterBag, TranslatorInterface $translator, RouterInterface $router)
    {
        $this->practicalSubmoduleService = $practicalSubmoduleService;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
        $this->router = $router;
    }

    public function generateDocumentFromAssessment(PracticalSubmoduleAssessment $assessment, string $locale): string
    {
        return match ($assessment->getPracticalSubmodule()->getExportType()) {
            PracticalSubmodule::EXPORT_TYPE_PRIVACY_POLICY => $this->generatePrivacyPolicyDocument($assessment),
            PracticalSubmodule::EXPORT_TYPE_PERSONAL_DATA_PROCESSING_CONSENT => $this->generatePersonalDataProcessingConsentDocument($assessment, $locale),
            PracticalSubmodule::EXPORT_TYPE_LIA => $this->generateLegitimateInterestAssessmentDocument($assessment, $locale),
            default => $this->generateDefaultDocument($assessment)
        };
    }

    public function generateCourseCertificateForUser(Course $course, User $user): string
    {
        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'word', 'certificate.docx');
        $templateProcessor = new TemplateProcessor($templateFile);
        $courseUrl = str_replace(['http://', 'https://'], '', $this->router->generate('course_overview', ['course' => $course->getId()], UrlGeneratorInterface::ABSOLUTE_URL));
        $now = new \DateTime();

        // Postavi jednostavne podatke
        $templateProcessor->setValue('person', $user->getNameOrEmail());
        $templateProcessor->setValue('module', $course->getName());
        $templateProcessor->setValue('url', $courseUrl);
        $templateProcessor->setValue('date', $now->format('d/m/Y'));
        $templateProcessor->setValue('workload', $this->translateCourseWorkload($course));

        // Postavi ishode učenja


        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $templateProcessor->saveAs($document);
        return $document;
    }

    private function generatePrivacyPolicyDocument(PracticalSubmoduleAssessment $assessment): string
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        usort($results, function (ProcessorResult $prA, ProcessorResult $prB) {
            $positionA = $prA->getProcessorGroup()->getPosition();
            $positionB = $prB->getProcessorGroup()->getPosition();
            if ($positionA === $positionB) return 0;
            return $positionA > $positionB ? 1 : -1;
        });

        $groupedResults = [];
        foreach ($results as $result) {
            $processorGroupId = $result->getProcessorGroup()->getId();
            if (false === key_exists($processorGroupId, $groupedResults)) {
                $groupedResults[$processorGroupId] = ['title' => $result->getProcessorGroup()->getTitle(), 'results' => []];
            }
            $groupedResults[$processorGroupId]['results'][] = $result;
        }
        $groupCount = count($groupedResults);

        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'word', 'ps_export_template.docx');
        $templateProcessor = new TemplateProcessor($templateFile);

        ### Postavi naslov i opis.
        $templateProcessor->setValue('title', $assessment->getPracticalSubmodule()->getName());
        $templateProcessor->setValue('description', $assessment->getPracticalSubmodule()->getDescription());

        ### Postavi sadržaj.
        $templateProcessor->cloneBlock('block', $groupCount, true, true);
        $i = 1;
        foreach ($groupedResults as $_ => $groupedResult) {
            $resultCount = count($groupedResult['results']);
            $templateProcessor->setValue("blockTitle#$i", $groupedResult['title']);
            $templateProcessor->cloneBlock("blockContentWrapper#$i", $resultCount, true, true);

            $j = 1;
            /** @var ProcessorResult $result */
            foreach ($groupedResult['results'] as $result) {
                if ($result->isHtml()) {
                    $string = str_replace('<br>', '<br/>', $result->getText());
                    $html = new \DOMDocument('1.0', 'UTF-8');
                    $html->loadHTML(mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8'));
                    $body = $html->getElementsByTagName("body")->item(0);

                    $compositeText = new TextBox([]);
                    \PhpOffice\PhpWord\Shared\Html::addHtml($compositeText, $html->saveXML($body, LIBXML_NOEMPTYTAG), true);

                    $elementCount = $compositeText->countElements();
                    $expandedValues = new TextRun();
                    for ($k = 1; $k <= $elementCount; $k++) {
                        $expandedValues->addText(sprintf('${blockContent#%d#%d#%d}', $i, $j, $k));
                        if ($k < $elementCount) {
                            $expandedValues->addTextBreak();
                        }
                    }
                    $templateProcessor->setComplexValue("blockContent#$i#$j", $expandedValues);

                    $k = 1;
                    foreach ($compositeText->getElements() as $element) {
                        $templateProcessor->setComplexValue("blockContent#$i#$j#$k", $element);
                        $k++;
                    }
                } else {
                    $text = str_replace("\n", '<w:br/>', $result->getText());
                    $text = new Text($text, ['name' => 'Constantia', 'size' => 12]);
                    $templateProcessor->setComplexValue("blockContent#$i#$j", $text);
                }
                $j++;
            }

            $i++;
        }

        ### Postavi napomenu.
        if (null !== $assessment->getPracticalSubmodule()->getReportComment()) {
            $comment = str_replace("\r", '', $assessment->getPracticalSubmodule()->getReportComment());
            $comment = explode("\n", $comment);
            $compositeComment = [];
            foreach ($comment as $line) {
                $compositeComment[] = new Text($line);
            }

            $elementCount = count($compositeComment);
            $templateProcessor->cloneBlock('commentBlock', $elementCount, true, true);

            $i = 1;
            foreach ($compositeComment as $item) {
                $templateProcessor->setComplexValue("comment#$i", $item);
                $i++;
            }
        }
        else {
            $templateProcessor->cloneBlock('commentBlock', 0);
        }


        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $templateProcessor->saveAs($document);
        return $document;
    }

    private function generatePersonalDataProcessingConsentDocument(PracticalSubmoduleAssessment $assessment, string $locale)
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'word', $locale, 'ps_export_template_cpdp.docx');
        $templateProcessor = new TemplateProcessor($templateFile);
        $variables = $templateProcessor->getVariables();

        $callbackTrim = function (string $string) {
            return trim($string);
        };

        $callbackIsNotEmpty = function (string $string) {
            return strlen($string) > 0;
        };

        $templateProcessor->setValue('title', $assessment->getPracticalSubmodule()->getName());
        foreach ($results as $result) {
            $exportTag = $result->getExportTag();
            if (null === $exportTag) {
                continue;
            }

            $blockName = 'list_'.$exportTag;
            if (in_array($blockName, $variables)) {
                $list = explode("\n", $result->getText());
                $list = array_map($callbackTrim, $list);
                $list = array_filter($list, $callbackIsNotEmpty);
                $listSize = count($list);
                $templateProcessor->cloneBlock($blockName, $listSize, true, true);

                $i = 1;
                foreach ($list as $item) {
                    $templateProcessor->setValue("$exportTag#$i", $item);
                    $i++;
                }
            } else {
                $templateProcessor->setValue($result->getExportTag(), str_replace("\n", '<w:br/>', $result->getText()));
            }
        }

        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $templateProcessor->saveAs($document);
        return $document;
    }

    private function generateLegitimateInterestAssessmentDocument(PracticalSubmoduleAssessment $assessment, string $locale)
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'word', $locale, 'ps_export_template_lia.docx');
        $templateProcessor = new TemplateProcessor($templateFile);

        foreach ($results as $result) {
            $exportTag = $result->getExportTag();
            if (null === $exportTag) {
                continue;
            }
            $this->handleTemplatingForLIA($exportTag, $templateProcessor, $result->getText());
        }

        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $templateProcessor->saveAs($document);
        return $document;
    }

    private function generateDefaultDocument(PracticalSubmoduleAssessment $assessment): string|false
    {
        $word = new \PhpOffice\PhpWord\PhpWord();
        $word->setDefaultParagraphStyle(['spaceAfter' => 0, 'alignment' => 'both']);
        $modeOfOperation = $assessment->getPracticalSubmodule()->getModeOfOperation();

        if (PracticalSubmodule::MODE_OF_OPERATION_ADVANCED === $modeOfOperation) {
            $this->handleForAdvancedModeOfOperation($word, $assessment);
        } else if (PracticalSubmodule::MODE_OF_OPERATION_SIMPLE === $modeOfOperation) {
            $this->handleForSimpleModeOfOperation($word, $assessment);
        }

        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($word);
        $writer->save($document);
        return $document;
    }

    private function handleForAdvancedModeOfOperation(\PhpOffice\PhpWord\PhpWord $word, PracticalSubmoduleAssessment $assessment): void
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $section = $word->addSection();

        foreach ($results as $result) {
            if (false === $result->isTextSet()) {
                continue;
            }
            $this->handleResult($result, $section);
            $section->addTextBreak();
        }
    }

    private function handleForSimpleModeOfOperation(\PhpOffice\PhpWord\PhpWord $word, PracticalSubmoduleAssessment $assessment): void
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $section = $word->addSection();
        $data = [];

        foreach ($results as $result) {
            $item = ['result' => $result, 'answers' => []];
            if ($result->isQuestionSet()) {
                foreach ($assessment->getPracticalSubmoduleAssessmentAnswers() as $answer) {
                    if ($answer->getPracticalSubmoduleQuestion()->getId() !== $result->getQuestion()->getId()) continue;
                    $item['answers'][] = $answer->getDisplayableAnswer();
                }
            }
            $data[] = $item;
        }

        foreach ($data as $item) {
            /** @var ProcessorResult $result */
            $result = $item['result'];

            if (true === $result->isQuestionSet()) {
                $section->addText($result->getQuestion()->getQuestionText())->getFontStyle()->setStyleValue('bold', true);
                foreach ($item['answers'] as $answer) {
                    $section->addListItem($this->translator->trans($answer, [], 'app'))->getTextObject()->getFontStyle()->setStyleValue('bold', true);
                }
                $section->addTextBreak();
            }

            if (true === $result->isTextSet()) {
                $this->handleResult($result, $section);
            }

            $section->addTextBreak(2);
        }
    }

    private function handleResult(ProcessorResult $result, \PhpOffice\PhpWord\Element\Section $section): void
    {
        if (true === $result->isHtml()) {
            $string = str_replace('<br>', '<br/>', $result->getText());
            $html = new \DOMDocument('1.0', 'UTF-8');
            $html->loadHTML(mb_convert_encoding($string, 'HTML-ENTITIES', 'UTF-8'));
            $body = $html->getElementsByTagName("body")->item(0);
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html->saveXML($body, LIBXML_NOEMPTYTAG), true);
        } else {
            foreach (explode("\n", $result->getText()) as $line) {
                if (str_starts_with($line, '- ')) {
                    $section->addListItem(preg_replace('/^- /', '', $line, 1));
                } else {
                    $section->addText(trim($line), ['name' => 'Arial', 'size' => 10]);
                }
            }
        }
    }

    private function handleTemplatingForLIA(string $variable, TemplateProcessor $templateProcessor, string $text): void
    {
        $lines = explode("\n", str_replace("\r", '', $text));
        $linesCount = count($lines);
        $templateProcessor->cloneBlock("block_$variable", $linesCount, indexVariables: true);
        $i = 1;
        foreach ($lines as $line) {
            $fontStyle = null;
            if (str_ends_with($line, '|distinguish')) {
                $fontStyle = ['color' => '4472C4', 'bold' => true];
                $line = str_replace('|distinguish', '', $line);
            }
            $templateProcessor->setComplexValue("$variable#$i", new Text($line, $fontStyle));
            $i++;
        }
    }

    private function translateCourseWorkload(Course $course): string
    {
        $workload = $course->getEstimatedWorkload();
        if (!empty($workload)) {
            list($value, $time) = explode(' ', $workload);
            return match ($time) {
                'H' => $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.hours', [], 'app', $this->parameterBag->get('locale.default')),
                'D' => $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.days', [], 'app', $this->parameterBag->get('locale.default')),
                'W' => $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.weeks', [], 'app', $this->parameterBag->get('locale.default')),
                'M' => $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.months', [], 'app', $this->parameterBag->get('locale.default')),
                'Y' => $value . ' ' . $this->translator->trans('form.entity.course.choices.estimatedWorkload.years', [], 'app', $this->parameterBag->get('locale.default')),
                default => $value
            };
        }
        return '';
    }
}
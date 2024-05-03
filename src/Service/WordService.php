<?php

namespace App\Service;

use App\Entity\Course;
use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\User;
use App\Misc\ProcessorResult;
use PhpOffice\PhpWord\Element\Link;
use PhpOffice\PhpWord\Element\ListItem;
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
            PracticalSubmodule::EXPORT_TYPE_LIA                              => $this->generateLegitimateInterestAssessmentDocument($assessment, $locale),
            PracticalSubmodule::EXPORT_TYPE_DPIA                             => $this->generateDataProtectionImpactAssessment($assessment, $locale),
            PracticalSubmodule::EXPORT_TYPE_COOKIE_POLICY                    => $this->generateCookiePolicyDocument($assessment, $locale),
            PracticalSubmodule::EXPORT_TYPE_PRIVACY_POLICY                   => $this->generatePrivacyPolicyDocument($assessment, $locale),
            PracticalSubmodule::EXPORT_TYPE_RULEBOOK_ON_ISS                  => $this->generateRulebookOnISS($assessment, $locale),
            PracticalSubmodule::EXPORT_TYPE_RESPONDENTS_RIGHTS               => $this->generateRespondentsRightsDocument($assessment, $locale),
            PracticalSubmodule::EXPORT_TYPE_PERSONAL_DATA_PROCESSING_CONSENT => $this->generatePersonalDataProcessingConsentDocument($assessment, $locale),
            default                                                          => $this->generateDefaultDocument($assessment)
        };
    }

    private function generatePrivacyPolicyDocument(PracticalSubmoduleAssessment $assessment, string $locale): string
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'word', $locale, 'ps_export_template_pp.docx');
        $templateProcessor = new TemplateProcessor($templateFile);
        $fontStyle = ['name' => 'Constantia', 'size' => 12];
        $boldFontStyle = ['name' => 'Constantia', 'size' => 12, 'bold' => true];

        foreach ($results as $result) {
            $text = $result->getText();
            $text = str_replace('</p><p>', "\n", $text);
            $text = str_replace('</strong>', '|bold', $text);
            $text = strip_tags($text);
            $text = str_replace("\r", '', $text);
            $text = html_entity_decode($text);

            $lines = explode("\n", $text);
            $textRun = new TextRun();
            foreach ($lines as $line) {
                if (str_ends_with($line, '|bold')) {
                    $line = str_replace('|bold', '', $line);
                    $textRun->addText($line, $boldFontStyle);
                } else {
                    $textRun->addText($line, $fontStyle);
                }
                $textRun->addTextBreak();
            }
            $templateProcessor->setComplexValue($result->getExportTag(), $textRun);
        }

        $processors = $this->practicalSubmoduleService->findRunnableProcessors($assessment->getPracticalSubmodule());
        foreach ($processors as $processor) {
            $templateProcessor->setValue($processor->getExportTag(), '');
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

        $templateProcessor->setValue('date', (new \DateTime())->format('d.m.Y.'));
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

    private function generateCookiePolicyDocument(PracticalSubmoduleAssessment $assessment, string $locale)
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $lines = explode("\n", str_replace("\r", '', $results[0]->getText()));
        $linesData = [];
        foreach ($lines as $line) {
            $lineData = ['text' => strip_tags($line), 'fontStyle' => []];
            if (str_starts_with($line, '<h') || str_starts_with($line, '<b')) {
                $lineData['fontStyle']['bold'] = true;
            }
            $linesData[] = $lineData;
        }
        $lineCount = count($lines);

        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'word', $locale, 'ps_export_template_cp.docx');
        $templateProcessor = new TemplateProcessor($templateFile);
        $templateProcessor->cloneBlock('blockContentWrapper', $lineCount, true, true);

        $i = 1;
        foreach ($linesData as $lineData) {
            $text = new Text($lineData['text'], $lineData['fontStyle']);
            $templateProcessor->setComplexValue("blockContent#$i", $text);
            $i++;
        }

        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $templateProcessor->saveAs($document);
        return $document;
    }

    private function generateDataProtectionImpactAssessment(PracticalSubmoduleAssessment $assessment, string $locale)
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'word', $locale, 'ps_export_template_dpia.docx');
        $templateProcessor = new TemplateProcessor($templateFile);
        $fontStyle = ['name' => 'Bell MT', 'size' => 12];

        foreach ($results as $result) {
            $lines = explode("\n", str_replace("\r", '', $result->getText()));
            $textRun = new TextRun();
            $textRun->addText(array_shift($lines), $fontStyle);
            foreach ($lines as $line) {
                $textRun->addTextBreak();
                $textRun->addText($line, $fontStyle);
            }
            $templateProcessor->setComplexValue($result->getExportTag(), $textRun);
        }

        $processors = $this->practicalSubmoduleService->findRunnableProcessors($assessment->getPracticalSubmodule());
        foreach ($processors as $processor) {
            $templateProcessor->setValue($processor->getExportTag(), '');
        }

        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $templateProcessor->saveAs($document);
        return $document;
    }

    private function generateRespondentsRightsDocument(PracticalSubmoduleAssessment $assessment, string $locale)
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'word', $locale, 'ps_export_template_rr.docx');
        $templateProcessor = new TemplateProcessor($templateFile);

        $rr02Processed = false;
        $rr03Processed = false;

        foreach ($results as $result) {
            $exportTag = strtolower($result->getExportTag());
            switch ($exportTag) {
                case 'rr_02': {
                    $items = explode("\n", str_replace(['- ', "\r"], '', $result->getText()));
                    $itemCount = count($items);
                    $templateProcessor->cloneBlock('rr_02', $itemCount, indexVariables: true);
                    for ($i = 0, $j = 1; $i < $itemCount; $i++, $j++) {
                        $templateProcessor->setValue("rr_02_item#$j", $items[$i]);
                    }
                    $rr02Processed = true;
                    break;
                }
                case 'rr_03': {
                    $templateProcessor->cloneBlock('rr_03');
                    $rr03Processed = true;
                    break;
                }
                default: {
                    $templateProcessor->setValue($result->getExportTag(), $result->getText());
                }
            }
        }

        if (!$rr02Processed) $templateProcessor->cloneBlock('rr_02', 0);
        if (!$rr03Processed) $templateProcessor->cloneBlock('rr_03', 0);

        $processors = $this->practicalSubmoduleService->findRunnableProcessors($assessment->getPracticalSubmodule());
        foreach ($processors as $processor) {
            $templateProcessor->setValue($processor->getExportTag(), '');
        }

        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $templateProcessor->saveAs($document);
        return $document;
    }

    private function generateRulebookOnISS(PracticalSubmoduleAssessment $assessment, string $locale)
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'word', $locale, 'ps_export_template_psis.docx');
        $templateProcessor = new TemplateProcessor($templateFile);

        $processingStates = ['psis_03' => false, 'psis_04' => false, 'psis_05' => false];

        foreach ($results as $result) {
            $exportTag = strtolower($result->getExportTag());
            if (in_array($exportTag, ['psis_03', 'psis_04', 'psis_05'])) {
                $items = explode("\n", str_replace(['- ', "\r"], '', $result->getText()));
                $itemCount = count($items);
                $templateProcessor->cloneBlock($exportTag, $itemCount, indexVariables: true);
                for ($i = 0, $j = 1; $i < $itemCount; $i++, $j++) {
                    $templateProcessor->setValue("{$exportTag}_item#$j", $items[$i]);
                }
                $processingStates[$exportTag] = true;
            } else {
                $templateProcessor->setValue($result->getExportTag(), $result->getText());
            }
        }

        foreach ($processingStates as $tag => $processed) {
            if (!$processed) $templateProcessor->cloneBlock($tag, 0);
        }

        $processors = $this->practicalSubmoduleService->findRunnableProcessors($assessment->getPracticalSubmodule());
        foreach ($processors as $processor) {
            $templateProcessor->setValue($processor->getExportTag(), '');
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
            $fontStyle = [];
            if (str_ends_with($line, '|distinguish')) {
                $fontStyle['bold'] = true;
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
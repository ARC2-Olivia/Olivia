<?php

namespace App\Service;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Misc\ProcessorResult;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextBox;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Translation\TranslatorInterface;

class WordService
{
    private ?PracticalSubmoduleService $practicalSubmoduleService = null;
    private ?ParameterBagInterface $parameterBag = null;
    private ?TranslatorInterface $translator = null;

    public function __construct(PracticalSubmoduleService $practicalSubmoduleService, ParameterBagInterface $parameterBag, TranslatorInterface $translator)
    {
        $this->practicalSubmoduleService = $practicalSubmoduleService;
        $this->parameterBag = $parameterBag;
        $this->translator = $translator;
    }

    public function generateDocumentFromAssessment(PracticalSubmoduleAssessment $assessment): string
    {
        return PracticalSubmodule::EXPORT_TYPE_PRIVACY_POLICY === $assessment->getPracticalSubmodule()->getExportType()
            ? $this->generatePrivacyPolicyDocument($assessment)
            : $this->generateDefaultDocument($assessment);
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
}
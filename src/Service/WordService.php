<?php

namespace App\Service;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Misc\ProcessorResult;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

    public function generateAssessmentResultsDocument(PracticalSubmoduleAssessment $assessment): string
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
            $html = new \DOMDocument();
            $html->loadHTML(str_replace('<br>', '<br/>', $result->getText()));
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
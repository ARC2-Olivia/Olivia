<?php

namespace App\Service;

use App\Entity\PracticalSubmoduleAssessment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class WordService
{
    private ?PracticalSubmoduleService $practicalSubmoduleService = null;
    private ?ParameterBagInterface $parameterBag = null;

    public function __construct(PracticalSubmoduleService $practicalSubmoduleService, ParameterBagInterface $parameterBag)
    {
        $this->practicalSubmoduleService = $practicalSubmoduleService;
        $this->parameterBag = $parameterBag;
    }

    public function generateAssessmentResultsDocument(PracticalSubmoduleAssessment $assessment): string
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);

        $word = new \PhpOffice\PhpWord\PhpWord();
        $word->setDefaultParagraphStyle(['spaceAfter' => 0, 'alignment' => 'both']);

        $section = $word->addSection();
        foreach ($results as $result) {
            if (false === $result->isTextSet()) {
                continue;
            }

            if ($result->isHtml()) {
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
            $section->addTextBreak();
        }

        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($word);
        $writer->save($document);

        return $document;
    }
}
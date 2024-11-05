<?php

namespace App\Service;

use App\Entity\PracticalSubmodule;
use App\Entity\PracticalSubmoduleAssessment;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Cell\CellAddress;
use PhpOffice\PhpSpreadsheet\Cell\CellRange;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExcelService
{
    private ?PracticalSubmoduleService $practicalSubmoduleService = null;
    private ?ParameterBagInterface $parameterBag = null;
    private ?EntityManagerInterface $em = null;
    private ?TranslatorInterface $translator = null;

    public function __construct(PracticalSubmoduleService $practicalSubmoduleService, ParameterBagInterface $parameterBag, EntityManagerInterface $em, TranslatorInterface $translator)
    {
        $this->practicalSubmoduleService = $practicalSubmoduleService;
        $this->parameterBag = $parameterBag;
        $this->em = $em;
        $this->translator = $translator;
    }

    public function generateDocumentFromAssessment(PracticalSubmoduleAssessment $assessment, string $locale): ?string
    {
        return match ($assessment->getPracticalSubmodule()->getExportType()) {
            PracticalSubmodule::EXPORT_TYPE_RECORDS_OF_PROCESSING_ACTIVITIES_DC => $this->generateRecordsOfProcessingActivitiesDC($assessment, $locale),
            PracticalSubmodule::EXPORT_TYPE_RECORDS_OF_PROCESSING_ACTIVITIES_DP => $this->generateRecordsOfProcessingActivitiesDP($assessment, $locale),
            default => null
        };
    }

    public function generateUsersDocument(): ?string
    {
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setCellValue('A1', $this->translator->trans('common.id', [], 'app'));
        $worksheet->setCellValue('B1', $this->translator->trans('form.entity.user.label.email', [], 'app'));
        $worksheet->setCellValue('C1', $this->translator->trans('form.entity.user.label.roles', [], 'app'));

        $users = $this->em->getRepository(User::class)->findAll();
        $rowOffset = 0;
        $cellCoordinate = 'A2';
        foreach ($users as $user) {
            $cellAddress = (new CellAddress($cellCoordinate, $worksheet))->nextRow($rowOffset);
            $worksheet->setCellValue($cellAddress, $user->getId());
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, $user->getEmail());
            $cellAddress = $cellAddress->nextColumn();
            $worksheet->setCellValue($cellAddress, implode(',', $user->getRoles()));
            $rowOffset++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $document = tempnam($this->parameterBag->get('dir.temp'), 'users-');
        $writer->save($document);
        return $document;
    }

    private function generateRecordsOfProcessingActivitiesDC(PracticalSubmoduleAssessment $assessment, string $locale): string
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'excel', $locale, 'ps_export_template_rpadc.xlsx');
        $columnCount = 25;

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($templateFile);
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($results as $result) {
            if (null === $result->getExportTag()) continue;

            if ('A9' === $result->getExportTag()) {
                $lines = explode('/*/', preg_replace('/\/\*\/(\n|\r\|\r\n)/', '/*/', $result->getText()));
                $lines = array_filter($lines, function ($line) { return strlen(trim($line)) > 0; });
                $this->copyRowStyles($worksheet, $result->getExportTag(), $columnCount, count($lines));

                $coordinate = $worksheet->getCell($result->getExportTag())->getCoordinate();
                $rowOffset = 0;
                foreach ($lines as $line) {
                    $cellAddress = (new CellAddress($coordinate, $worksheet))->nextRow($rowOffset);
                    foreach (explode('|*|', $line) as $value) {
                        $currentCell = $worksheet->getCell($cellAddress);
                        $currentCell->setValue($value);
                        $cellAddress = $cellAddress->nextColumn();
                    }
                    $rowOffset++;
                }
            } else {
                $worksheet->getCell($result->getExportTag())->setValue($result->getText());
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $writer->save($document);
        return $document;
    }

    private function generateRecordsOfProcessingActivitiesDP(PracticalSubmoduleAssessment $assessment, string $locale): string
    {
        $results = $this->practicalSubmoduleService->runProcessors($assessment);
        $templateFile = Path::join($this->parameterBag->get('kernel.project_dir'), 'assets', 'excel', $locale, 'ps_export_template_rpadp.xlsx');
        $columnCount = 8;

        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $spreadsheet = $reader->load($templateFile);
        $worksheet = $spreadsheet->getActiveSheet();

        foreach ($results as $result) {
            if (null === $result->getExportTag()) continue;

            if ('A9' === $result->getExportTag()) {
                $lines = explode('/*/', preg_replace('/\/\*\/(\n|\r\|\r\n)/', '/*/', $result->getText()));
                $lines = array_filter($lines, function ($line) { return strlen(trim($line)) > 0; });
                $this->copyRowStyles($worksheet, $result->getExportTag(), $columnCount, count($lines));

                $coordinate = $worksheet->getCell($result->getExportTag())->getCoordinate();
                $rowOffset = 0;
                foreach ($lines as $line) {
                    $cellAddress = (new CellAddress($coordinate, $worksheet))->nextRow($rowOffset);
                    foreach (explode('|*|', $line) as $value) {
                        $currentCell = $worksheet->getCell($cellAddress);
                        $currentCell->setValue($value);
                        $cellAddress = $cellAddress->nextColumn();
                    }
                    $rowOffset++;
                }
            } else {
                $worksheet->getCell($result->getExportTag())->setValue($result->getText());
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $document = tempnam($this->parameterBag->get('dir.temp'), 'word-');
        $writer->save($document);
        return $document;
    }

    private function copyRowStyles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $worksheet, string $startingCellCoordinate, int $columnCount, int $amount): void
    {
        $styles = [];
        $cellAddress = new CellAddress($startingCellCoordinate);
        for ($i = 0; $i < $columnCount; $i++) {
            $columnIndex = $cellAddress->columnId() + $i;
            $styles[] = $worksheet->getStyle(new CellAddress(Coordinate::stringFromColumnIndex($columnIndex) . $cellAddress->rowId()))->exportArray();
        }

        for ($i = 0; $i < $amount; $i++) {
            $rowIndex = $cellAddress->rowId() + $i;
            for ($j = 0; $j < $columnCount; $j++) {
                $columnIndex = $cellAddress->columnId() + $j;
                $worksheet->getStyle(new CellAddress(Coordinate::stringFromColumnIndex($columnIndex) . $rowIndex))->applyFromArray($styles[$j]);
            }
        }
    }
}
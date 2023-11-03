<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;

class WkhtmltopdfService
{
    private ?ParameterBagInterface $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }

    public function makePdf(string $html, string $commandLineArguments = ""): ?\SplFileInfo
    {
        $fs = new Filesystem();
        $tempDir = $this->getTempDirectoryPath();
        if (false === $fs->exists($tempDir)) {
            $fs->mkdir($tempDir);
        }

        try {
            $output = $fs->tempnam($tempDir, 'wkout-', '.pdf');
            $tempHtml = $fs->tempnam($tempDir, 'wkin-', '.html');
            $fs->dumpFile($tempHtml, $html);

            $wkhtmltopdfShellPath = escapeshellarg($this->getWkhtmltopdfExecutablePath());
            $tempHtmlShellPath = escapeshellarg($tempHtml);
            $outputShellPath = escapeshellarg($output);

            `$wkhtmltopdfShellPath ${commandLineArguments} file:///$tempHtmlShellPath $outputShellPath`;
        } catch (\Exception $ex) {
            if (is_string($tempHtml)) $fs->remove($tempHtml);
            if (is_string($output)) $fs->remove($output);
            return null;
        }

        $fs->remove($tempHtml);
        return new \SplFileInfo($output);
    }

    public function makeLandscapePdf(string $html): ?\SplFileInfo
    {
        return $this->makePdf($html, '--page-size A4 --orientation Landscape --encoding utf-8 --disable-smart-shrinking --enable-local-file-access -T 0mm -B 0mm -L 0mm -R 0mm');
    }

    public function makePortraitPdf(string $html): ?\SplFileInfo
    {
        return $this->makePdf($html, '--page-size A4 --orientation Portrait --encoding utf-8 --disable-smart-shrinking --enable-local-file-access -T 0mm -B 0mm -L 0mm -R 0mm');
    }

    protected function getTempDirectoryPath(): string
    {
        return $this->parameterBag->get('dir.temp');
    }

    protected function getWkhtmltopdfExecutablePath(): string
    {
        return $this->parameterBag->get('wkhtmltopdf');
    }
}
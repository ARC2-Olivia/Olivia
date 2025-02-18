<?php

namespace App\Traits;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait BasicFileManagementTrait
{
    private function storeFile(UploadedFile $file, string $dir, string $filenamePrefix = null): string
    {
        $filename = uniqid() . '.' . $file->guessClientExtension();
        if ($filenamePrefix) $filename = $filenamePrefix . $filename;
        $file->move($dir, $filename);
        return $filename;
    }

    private function removeFile(string $file): void
    {
        $fs = new Filesystem();
        $fs->remove($file);
    }
}
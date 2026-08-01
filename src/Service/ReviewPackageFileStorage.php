<?php

namespace App\Service;

use App\Entity\ReviewPackageFile;

class ReviewPackageFileStorage
{
    public function __construct(
        private string $appDirsFilesLocal,
    ) {
    }

    public function getAbsolutePath(ReviewPackageFile $file): string
    {
        return $this->resolvePath($file->getFilename());
    }

    public function resolvePath(string $relativeFilename): string
    {
        $base = rtrim($this->appDirsFilesLocal, '/\\');
        $relative = ltrim(str_replace(['\\', '..'], ['/', ''], $relativeFilename), '/');

        return $base.\DIRECTORY_SEPARATOR.str_replace('/', \DIRECTORY_SEPARATOR, $relative);
    }

    public function ensureDirectory(string $relativeDir): string
    {
        $path = $this->resolvePath($relativeDir);
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Не удалось создать каталог %s', $path));
        }

        return $path;
    }

    public function copyIntoPackage(string $sourceAbsolutePath, string $packageGuid, string $displayName): string
    {
        if (!is_file($sourceAbsolutePath)) {
            throw new \InvalidArgumentException(sprintf('Исходный файл не найден: %s', $sourceAbsolutePath));
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._\-а-яА-ЯёЁ ]+/u', '_', $displayName) ?: 'document.docx';
        $relativeDir = 'review_packages/'.$packageGuid;
        $this->ensureDirectory($relativeDir);
        $relativeFilename = $relativeDir.'/'.$safeName;
        $target = $this->resolvePath($relativeFilename);

        if (!copy($sourceAbsolutePath, $target)) {
            throw new \RuntimeException(sprintf('Не удалось скопировать файл в %s', $target));
        }

        return $relativeFilename;
    }
}

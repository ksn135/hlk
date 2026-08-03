<?php

namespace App\Service;

use App\Entity\Contractor;
use App\Entity\ReviewPackageFile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OnlyOfficeService
{
    public function __construct(
        private OnlyOfficeAccessTokenService $accessTokenService,
        private OnlyOfficeUrlResolver $urlResolver,
        private ReviewPackageFileStorage $fileStorage,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @return array{config: array<string, mixed>, mode: string}
     */
    public function buildEditorContext(ReviewPackageFile $file, Contractor $user, string $requestedMode): array
    {
        if (!$file->isWord()) {
            throw new \LogicException('OnlyOffice доступен только для файлов Word.');
        }

        $package = $file->getPackage();
        if (null === $package) {
            throw new \LogicException('Файл не привязан к пакету.');
        }

        $canEdit = 'edit' === $requestedMode && $package->getStatus()->isEditable();
        $mode = $canEdit ? 'edit' : 'view';

        $accessToken = $this->accessTokenService->createToken(
            ReviewPackageFile::class,
            (string) $file->getId(),
            (int) $user->getId()
        );

        $absolutePath = $this->fileStorage->getAbsolutePath($file);
        if (!is_file($absolutePath)) {
            throw new \LogicException('Файл "'.$absolutePath.'" не найден на диске.');
        }

        $appBaseUrl = $this->urlResolver->getDocumentServerAppBaseUrl();
        $downloadUrl = $appBaseUrl.$this->urlGenerator->generate(
            'onlyoffice_download',
            ['token' => $accessToken],
            UrlGeneratorInterface::ABSOLUTE_PATH
        );
        $callbackUrl = $appBaseUrl.$this->urlGenerator->generate(
            'onlyoffice_callback',
            ['token' => $accessToken],
            UrlGeneratorInterface::ABSOLUTE_PATH
        );

        $extension = strtolower(pathinfo($file->getDisplayName(), \PATHINFO_EXTENSION)) ?: 'docx';

        $config = [
            'document' => [
                'fileType' => $extension,
                'key' => $this->buildDocumentKey($file, $absolutePath),
                'title' => $file->getDisplayName(),
                'url' => $downloadUrl,
                'permissions' => [
                    'edit' => $canEdit,
                    'download' => true,
                    'print' => true,
                    'review' => $canEdit,
                    'comment' => $canEdit,
                    'fillForms' => $canEdit,
                    'modifyFilter' => $canEdit,
                    'copy' => true,
                ],
            ],
            'documentType' => 'word',
            'editorConfig' => [
                'mode' => $mode,
                'lang' => 'ru',
                'callbackUrl' => $callbackUrl,
                'user' => [
                    'id' => (string) $user->getId(),
                    'name' => (string) $user,
                ],
                'customization' => [
                    'forcesave' => true,
                ],
            ],
        ];

        if ($this->accessTokenService->isJwtEnabled()) {
            $config['token'] = $this->accessTokenService->signJwt([
                'document' => $config['document'],
                'documentType' => $config['documentType'],
                'editorConfig' => $config['editorConfig'],
            ]);
        }

        return [
            'config' => $config,
            'mode' => $mode,
        ];
    }

    private function buildDocumentKey(ReviewPackageFile $file, string $absolutePath): string
    {
        $mtime = (string) filemtime($absolutePath);

        return substr(hash('sha256', ReviewPackageFile::class.':'.$file->getId().':'.$mtime), 0, 20);
    }
}

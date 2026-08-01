<?php

namespace App\Controller;

use App\Entity\Contractor;
use App\Entity\ReviewPackageFile;
use App\Repository\ReviewPackageFileRepository;
use App\Service\OnlyOfficeAccessTokenService;
use App\Service\OnlyOfficeService;
use App\Service\OnlyOfficeUrlResolver;
use App\Service\ReviewPackageFileStorage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class OnlyOfficeController extends AbstractController
{
    public function __construct(
        private OnlyOfficeService $onlyOfficeService,
        private OnlyOfficeUrlResolver $urlResolver,
        private OnlyOfficeAccessTokenService $accessTokenService,
        private ReviewPackageFileStorage $fileStorage,
        private ReviewPackageFileRepository $fileRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    #[Route('/onlyoffice/editor/{id}/{mode}', name: 'onlyoffice_editor', requirements: ['mode' => 'view|edit'], defaults: ['mode' => 'view'], methods: ['GET'])]
    #[IsGranted('ROLE_CONTRACTOR')]
    public function editor(int $id, string $mode): Response
    {
        $file = $this->fileRepository->find($id);
        if (!$file instanceof ReviewPackageFile) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        if (!$user instanceof Contractor) {
            throw $this->createAccessDeniedException();
        }

        $package = $file->getPackage();
        if (null === $package || $package->getContractorId() !== (int) $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $context = $this->onlyOfficeService->buildEditorContext($file, $user, $mode);
        $package->createLog('file_opened', $file->getDisplayName(), ['fileId' => $file->getId(), 'mode' => $context['mode']]);
        $this->em->flush();

        return $this->render('onlyoffice/editor.html.twig', [
            'config' => $context['config'],
            'scriptPath' => $this->urlResolver->getDocumentServerScriptPath(),
            'mode' => $context['mode'],
        ]);
    }

    #[Route('/onlyoffice/download/{token}', name: 'onlyoffice_download', methods: ['GET'])]
    public function download(string $token): Response
    {
        $file = $this->resolveTokenFile($token);
        $path = $this->fileStorage->getAbsolutePath($file);
        if (!is_file($path)) {
            throw new NotFoundHttpException();
        }

        $response = new BinaryFileResponse($path);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $file->getDisplayName());
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $response->headers->set('Cache-Control', 'private, no-cache');

        return $response;
    }

    #[Route('/onlyoffice/callback/{token}', name: 'onlyoffice_callback', methods: ['POST'])]
    public function callback(string $token, Request $request): JsonResponse
    {
        try {
            $file = $this->resolveTokenFile($token);
            $payload = $this->parseCallbackPayload($request);
            $status = (int) ($payload['status'] ?? 0);

            if (\in_array($status, [2, 6], true)) {
                $this->saveFileFromCallback($file, (string) ($payload['url'] ?? ''));
                $package = $file->getPackage();
                $package?->createLog('file_saved', $file->getDisplayName(), ['fileId' => $file->getId()]);
                $this->em->flush();
            }

            return new JsonResponse(['error' => 0]);
        } catch (\Throwable $e) {
            $this->logger->error('OnlyOffice callback failed: '.$e->getMessage());

            return new JsonResponse(['error' => 1, 'message' => $e->getMessage()]);
        }
    }

    private function resolveTokenFile(string $token): ReviewPackageFile
    {
        $data = $this->accessTokenService->parseToken($token);
        if (ReviewPackageFile::class !== $data['class']) {
            throw new NotFoundHttpException();
        }

        $file = $this->fileRepository->find((int) $data['id']);
        if (!$file instanceof ReviewPackageFile) {
            throw new NotFoundHttpException();
        }

        return $file;
    }

    /** @return array<string, mixed> */
    private function parseCallbackPayload(Request $request): array
    {
        $content = $request->getContent();
        if ('' === $content) {
            return [];
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        if (isset($decoded['token']) && \is_string($decoded['token'])) {
            return $this->accessTokenService->decodeJwtPayload($decoded['token']);
        }

        return $decoded;
    }

    private function saveFileFromCallback(ReviewPackageFile $file, string $url): void
    {
        if ('' === $url) {
            throw new \LogicException('OnlyOffice callback не содержит URL сохранённого файла.');
        }

        $download = $this->urlResolver->resolveCallbackCacheUrl($url);
        $headers = ['Accept: application/octet-stream'];
        if (null !== $download['host']) {
            $headers[] = 'Host: '.$download['host'];
            $headers[] = 'X-Forwarded-Proto: https';
        }
        if ($this->accessTokenService->isJwtEnabled()) {
            $headers[] = 'Authorization: Bearer '.$this->accessTokenService->signDownloadUrl($download['jwtUrl']);
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 120,
                'ignore_errors' => true,
                'header' => implode("\r\n", $headers),
            ],
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $content = file_get_contents($download['fetchUrl'], false, $context);
        if (false === $content || '' === $content) {
            throw new \LogicException('Не удалось скачать файл из OnlyOffice: '.$download['fetchUrl']);
        }

        $targetPath = $this->fileStorage->getAbsolutePath($file);
        if (false === file_put_contents($targetPath, $content)) {
            throw new \LogicException('Не удалось сохранить файл на диск.');
        }
        clearstatcache(true, $targetPath);
    }
}

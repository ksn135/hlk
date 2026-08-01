<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class OnlyOfficeUrlResolver
{
    public function __construct(
        private RequestStack $requestStack,
        private string $onlyOfficeUrl,
        private string $onlyOfficeAppUrl,
        private string $onlyOfficeDsAppUrl = '',
        private string $onlyOfficeInternalUrl = '',
    ) {
    }

    public function getDocumentServerScriptPath(): string
    {
        $url = trim($this->onlyOfficeUrl);
        if (str_starts_with($url, '/')) {
            return rtrim($url, '/').'/web-apps/apps/api/documents/api.js';
        }

        return rtrim($url, '/').'/web-apps/apps/api/documents/api.js';
    }

    public function getAppBaseUrl(): string
    {
        $appUrl = trim($this->onlyOfficeAppUrl);
        if ('' === $appUrl || 'auto' === $appUrl) {
            return $this->getRequestOrigin();
        }

        return rtrim($appUrl, '/');
    }

    public function getDocumentServerAppBaseUrl(): string
    {
        $dsUrl = trim($this->onlyOfficeDsAppUrl);
        if ('' !== $dsUrl) {
            return rtrim($dsUrl, '/');
        }

        return $this->getAppBaseUrl();
    }

    /**
     * @return array{fetchUrl: string, jwtUrl: string, host: ?string}
     */
    public function resolveCallbackCacheUrl(string $url): array
    {
        $originalUrl = html_entity_decode($url, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $fetchUrl = $this->rewriteCacheUrlToInternal($originalUrl);

        $originalHost = parse_url($originalUrl, \PHP_URL_HOST);
        $fetchHost = parse_url($fetchUrl, \PHP_URL_HOST);
        $host = \is_string($originalHost) && \is_string($fetchHost) && $originalHost !== $fetchHost
            ? $originalHost
            : null;

        return [
            'fetchUrl' => $fetchUrl,
            'jwtUrl' => $fetchUrl,
            'host' => $host,
        ];
    }

    private function rewriteCacheUrlToInternal(string $url): string
    {
        $internalBase = rtrim(trim($this->onlyOfficeInternalUrl), '/');
        if ('' === $internalBase) {
            return $url;
        }

        $ooPath = rtrim(trim($this->onlyOfficeUrl), '/');
        if (str_starts_with($ooPath, '/')) {
            $pattern = '#^https?://[^/]+'.preg_quote($ooPath, '#').'(/.*)$#';
            if (preg_match($pattern, $url, $matches)) {
                return $internalBase.$matches[1];
            }
        }

        return $url;
    }

    private function getRequestOrigin(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            return $request->getSchemeAndHttpHost();
        }

        if ('' !== trim($this->onlyOfficeAppUrl) && 'auto' !== trim($this->onlyOfficeAppUrl)) {
            return rtrim($this->onlyOfficeAppUrl, '/');
        }

        return 'http://localhost';
    }
}

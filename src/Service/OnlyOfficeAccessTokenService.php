<?php

namespace App\Service;

class OnlyOfficeAccessTokenService
{
    public function __construct(
        private string $onlyOfficeJwtSecret,
    ) {
    }

    public function createToken(string $fileClass, string $fileId, ?int $userId = null, int $ttlSeconds = 3600): string
    {
        $payload = [
            'class' => $fileClass,
            'id' => $fileId,
            'exp' => time() + $ttlSeconds,
        ];
        if (null !== $userId) {
            $payload['userId'] = $userId;
        }

        return $this->encode($payload);
    }

    /** @return array<string, mixed> */
    public function decodeJwtPayload(string $token): array
    {
        return $this->decode($token);
    }

    /** @return array{class: string, id: string, exp: int} */
    public function parseToken(string $token): array
    {
        $payload = $this->decode($token);
        if (($payload['exp'] ?? 0) < time()) {
            throw new \LogicException('Токен OnlyOffice устарел.');
        }

        if (empty($payload['class']) || empty($payload['id'])) {
            throw new \LogicException('Некорректный токен OnlyOffice.');
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    public function signJwt(array $payload): string
    {
        return $this->encode($payload);
    }

    public function isJwtEnabled(): bool
    {
        return '' !== trim($this->onlyOfficeJwtSecret);
    }

    public function signDownloadUrl(string $url): string
    {
        return $this->signJwt(['url' => $url]);
    }

    /** @param array<string, mixed> $payload */
    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], \JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, \JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$body, $this->onlyOfficeJwtSecret, true));

        return $header.'.'.$body.'.'.$signature;
    }

    /** @return array<string, mixed> */
    private function decode(string $token): array
    {
        $parts = explode('.', $token);
        if (3 !== \count($parts)) {
            throw new \LogicException('Некорректный формат токена OnlyOffice.');
        }

        [$header, $body, $signature] = $parts;
        $expected = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$body, $this->onlyOfficeJwtSecret, true));
        if (!hash_equals($expected, $signature)) {
            throw new \LogicException('Подпись токена OnlyOffice недействительна.');
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($this->base64UrlDecode($body), true, 512, \JSON_THROW_ON_ERROR);

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = \strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/'), true) ?: '';
    }
}

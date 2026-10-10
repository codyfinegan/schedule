<?php

declare(strict_types=1);

namespace Schedule\Services;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Models\SessionRepository;

final class SessionCookieService
{
    public const string COOKIE = '__Host-_botcsession';

    public function getCookieFromRequest(ServerRequestInterface $request): ?string
    {
        return $request->getCookieParams()[self::COOKIE] ?? null;
    }

    public function withSessionCookie(ResponseInterface $response, string $token): ResponseInterface
    {
        $cookie = $this->buildCookie(rawurlencode($token), SessionRepository::MAX_LIFETIME);
        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    public function withoutSessionCookie(ResponseInterface $response): ResponseInterface
    {
        $cookie = $this->buildCookie('', 0);
        return $response->withAddedHeader('Set-Cookie', $cookie);
    }

    /**
     * @param string $value
     * @param int $maxLifetime
     * @return string
     */
    public function buildCookie(string $value, int $maxLifetime): string
    {
        return sprintf(
            '%s=%s; Max-Age=%d; Path=/; Secure; HttpOnly; SameSite=Lax',
            self::COOKIE,
            $value,
            $maxLifetime
        );
    }
}

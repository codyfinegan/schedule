<?php

declare(strict_types=1);

namespace Schedule\Http\Middleware;

use League\Route\Http\Exception\ForbiddenException;
use League\Route\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Schedule\Models\Session;
use Schedule\Models\SessionRepository;

final class SessionMiddleware implements MiddlewareInterface
{
    public const string COOKIE = '__Host-_botcsession';

    public function __construct(
        private SessionRepository $sessionRepository,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookie = $request->getCookieParams()[self::COOKIE] ?? null;
        if (!is_string($cookie) || empty($cookie)) {
            throw new UnauthorizedException();
        }

        $hash = hash('sha256', $cookie);
        $now = time();
        $session = $this->sessionRepository->findValid($hash, $now);
        if ($session === null) {
            throw new UnauthorizedException();
        }

        $this->assertValidCsrf($request, $session);

        $session = $this->sessionRepository->touch($session, $now);
        $request = $request->withAttribute(Session::class, $session);

        return $handler->handle($request);
    }

    private function assertValidCsrf(ServerRequestInterface $request, Session $session): void
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        if (!hash_equals($session->csrfToken, $request->getHeaderLine('X-CSRF-Token'))) {
            throw new ForbiddenException();
        }
    }
}

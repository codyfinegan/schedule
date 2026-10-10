<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Auth;

use League\Route\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Models\Session;
use Schedule\Models\SessionRepository;
use Schedule\Services\SessionCookieService;

final class LogoutController
{
    public function __construct(
        private SessionRepository $sessionRepository,
        private SessionCookieService $sessionCookieService,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $session = $request->getAttribute(Session::class);
        if (!$session instanceof Session) {
            throw new UnauthorizedException();
        }

        // Delete the session record
        $this->sessionRepository->destroy($session);

        $response = $this->responseFactory->createResponse(204);
        return $this->sessionCookieService->withoutSessionCookie($response);
    }
}

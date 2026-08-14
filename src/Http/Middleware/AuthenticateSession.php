<?php

declare(strict_types=1);

namespace Schedule\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Schedule\Config;
use Schedule\Models\Session;

/**
 * Attaches the authenticated user (if any) to the request as the "user"
 * attribute. Never blocks the request itself -- controllers/route
 * middleware (e.g. RequireAdmin, or RequiresUser::currentUser()) decide
 * whether a given endpoint requires a logged-in user.
 */
final class AuthenticateSession implements MiddlewareInterface
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $cookies = $request->getCookieParams();
        $token = $cookies[$this->config->get('session.cookie_name')] ?? null;

        if (is_string($token) && $token !== '') {
            $tokenHash = hash('sha256', $token);

            $session = Session::query()
                ->where('token_hash', $tokenHash)
                ->where('expires_at', '>', gmdate('Y-m-d H:i:s'))
                ->first();

            if ($session !== null) {
                $user = $session->user;
                if ($user !== null) {
                    $request = $request
                        ->withAttribute('user', $user)
                        ->withAttribute('session', $session);
                }
            }
        }

        return $handler->handle($request);
    }
}

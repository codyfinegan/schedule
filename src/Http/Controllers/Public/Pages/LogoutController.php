<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Public\Pages;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Config;
use Schedule\Models\Session;

final class LogoutController
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute('session');
        if ($session instanceof Session) {
            $session->delete();
        }

        $secure = $this->config->get('session.secure', true) ? '; Secure' : '';
        $cookie = sprintf(
            '%s=; Expires=%s; Path=/; HttpOnly%s; SameSite=Lax',
            $this->config->get('session.cookie_name'),
            gmdate(DATE_COOKIE, 0),
            $secure,
        );

        return new Response(302, ['Location' => '/login', 'Set-Cookie' => $cookie]);
    }
}

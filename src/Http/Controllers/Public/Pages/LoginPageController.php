<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Public\Pages;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Concerns\RendersPage;
use Schedule\Models\User;
use Schedule\View\TemplateRenderer;

final class LoginPageController
{
    use RendersPage;

    public function __construct(TemplateRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        if ($request->getAttribute('user') instanceof User) {
            return new Response(302, ['Location' => '/blocks']);
        }

        return $this->renderPage($request, 'login', [
            'title' => 'Sign in',
            'entry' => 'login',
            'activeNav' => null,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Public\Pages;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Concerns\RendersPage;
use Schedule\View\TemplateRenderer;

final class NotFoundController
{
    use RendersPage;

    public function __construct(TemplateRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        return $this->renderPage($request, '404', [
            'title' => 'Not found',
            'entry' => null,
            'activeNav' => null,
        ], status: 404);
    }
}

<?php

declare(strict_types=1);

namespace Schedule\Http\Concerns;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Resources\UserResource;
use Schedule\Models\User;
use Schedule\View\TemplateRenderer;

trait RendersPage
{
    use RespondsWithHtml;

    /**
     * Assigned by each controller's own constructor (controllers that need
     * extra dependencies beyond the renderer can't rely on a trait-owned
     * constructor), e.g. `$this->renderer = $renderer;`.
     */
    protected readonly TemplateRenderer $renderer;

    /**
     * @param array $data Template variables. 'initialData' (if present) is
     *   the data injected into the page's #page-data script tag for the
     *   Vue island to read on mount instead of fetching it itself.
     */
    protected function renderPage(ServerRequestInterface $request, string $template, array $data = [], int $status = 200): ResponseInterface
    {
        $user = $request->getAttribute('user');
        $data['user'] = $user instanceof User ? UserResource::make($user) : null;
        $data['initialData'] ??= [];

        return $this->html($this->renderer->renderPage($template, $data), $status);
    }

    protected function redirectToLogin(ServerRequestInterface $request): ResponseInterface
    {
        $target = rawurlencode($request->getUri()->getPath());

        return new Response(302, ['Location' => "/login?redirect={$target}"]);
    }
}

<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Authenticated\Pages;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Concerns\RendersPage;
use Schedule\Http\Controllers\Authenticated\Api\BlocksController;
use Schedule\Models\User;
use Schedule\View\TemplateRenderer;

final class BlocksPageController
{
    use RendersPage;

    public function __construct(
        TemplateRenderer $renderer,
        private readonly BlocksController $blocksController,
    ) {
        $this->renderer = $renderer;
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if (!$user instanceof User) {
            return $this->redirectToLogin($request);
        }

        return $this->renderPage($request, 'blocks', [
            'title' => 'Games',
            'entry' => 'blocks',
            'activeNav' => 'blocks',
            'initialData' => [
                'initialUpcoming' => $this->blocksController->listForPage($user, 'upcoming'),
            ],
        ]);
    }
}

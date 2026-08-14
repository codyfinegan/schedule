<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Authenticated\Pages;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Concerns\RendersPage;
use Schedule\Http\Controllers\Authenticated\Api\BlocksController;
use Schedule\Models\User;
use Schedule\View\TemplateRenderer;

final class CalendarPageController
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

        // Mirrors CalendarView.vue's onMounted load(): both upcoming and
        // past blocks, in one combined list the client filters by date.
        $blocks = [
            ...$this->blocksController->listForPage($user, 'upcoming', perPage: 200),
            ...$this->blocksController->listForPage($user, 'past', perPage: 200),
        ];

        return $this->renderPage($request, 'calendar', [
            'title' => 'Calendar',
            'entry' => 'calendar',
            'activeNav' => 'calendar',
            'initialData' => [
                'initialBlocks' => $blocks,
            ],
        ]);
    }
}

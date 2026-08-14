<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Authenticated\Pages;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Concerns\RendersPage;
use Schedule\Http\Controllers\Authenticated\Api\AdminUsersController;
use Schedule\Http\Controllers\Authenticated\Api\BlocksController;
use Schedule\Http\Controllers\Authenticated\Api\RecurringSchedulesController;
use Schedule\Models\User;
use Schedule\View\TemplateRenderer;

final class AdminPageController
{
    use RendersPage;

    public function __construct(
        TemplateRenderer $renderer,
        private readonly BlocksController $blocksController,
        private readonly AdminUsersController $adminUsersController,
        private readonly RecurringSchedulesController $recurringSchedulesController,
    ) {
        $this->renderer = $renderer;
    }

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if (!$user instanceof User) {
            return $this->redirectToLogin($request);
        }

        if (!$user->is_admin) {
            return new Response(302, ['Location' => '/blocks']);
        }

        return $this->renderPage($request, 'admin', [
            'title' => 'Admin',
            'entry' => 'admin',
            'activeNav' => 'admin',
            'initialData' => [
                'initialUsers' => $this->adminUsersController->listForPage(),
                'initialBlocks' => $this->blocksController->listForPage($user, 'upcoming', perPage: 100),
                'initialSchedules' => $this->recurringSchedulesController->listForPage(),
            ],
        ]);
    }
}

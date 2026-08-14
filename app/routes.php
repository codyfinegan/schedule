<?php

use FastRoute\RouteCollector;
use Schedule\Http\Controllers\Authenticated\Api\AdminUsersController;
use Schedule\Http\Controllers\Authenticated\Api\BlocksController;
use Schedule\Http\Controllers\Authenticated\Api\MeController;
use Schedule\Http\Controllers\Authenticated\Api\RecurringSchedulesController;
use Schedule\Http\Controllers\Authenticated\Pages\AdminPageController;
use Schedule\Http\Controllers\Authenticated\Pages\BlocksPageController;
use Schedule\Http\Controllers\Authenticated\Pages\CalendarPageController;
use Schedule\Http\Controllers\Public\Api\AuthController;
use Schedule\Http\Controllers\Public\Pages\FrontendController;
use Schedule\Http\Controllers\Public\Pages\LoginPageController;
use Schedule\Http\Controllers\Public\Pages\LogoutController;
use Schedule\Http\Controllers\Public\Pages\NotFoundController;
use Schedule\Http\Middleware\RequireAdmin;

/**
 * Each route's handler data is ['handler' => [Controller::class, 'method'],
 * 'middleware' => [...]] -- RouteDispatcher builds a per-route inner
 * middleware queue from the 'middleware' key (e.g. RequireAdmin), keeping
 * admin checks out of the global queue.
 */
return function (RouteCollector $r): void {
    $route = fn (array $handler, array $middleware = []) => ['handler' => $handler, 'middleware' => $middleware];

    $r->addGroup('/api', function (RouteCollector $r) use ($route): void {
        $r->addRoute('POST', '/auth/request-code', $route([AuthController::class, 'requestCode']));
        $r->addRoute('POST', '/auth/verify-code', $route([AuthController::class, 'verifyCode']));
        $r->addRoute('POST', '/auth/logout', $route([AuthController::class, 'logout']));

        $r->addRoute('GET', '/me', $route([MeController::class, 'show']));

        $r->addRoute('GET', '/blocks', $route([BlocksController::class, 'index']));
        $r->addRoute('GET', '/blocks/{id:\d+}', $route([BlocksController::class, 'show']));
        $r->addRoute('POST', '/blocks', $route([BlocksController::class, 'store'], [RequireAdmin::class]));
        $r->addRoute('PATCH', '/blocks/{id:\d+}', $route([BlocksController::class, 'update'], [RequireAdmin::class]));
        $r->addRoute('DELETE', '/blocks/{id:\d+}', $route([BlocksController::class, 'destroy'], [RequireAdmin::class]));
        $r->addRoute('POST', '/blocks/{id:\d+}/rsvp', $route([BlocksController::class, 'rsvp']));
        $r->addRoute('DELETE', '/blocks/{id:\d+}/rsvp', $route([BlocksController::class, 'unrsvp']));

        $r->addRoute('GET', '/recurring-schedules', $route([RecurringSchedulesController::class, 'index']));
        $r->addRoute('POST', '/recurring-schedules', $route([RecurringSchedulesController::class, 'store'], [RequireAdmin::class]));
        $r->addRoute('PATCH', '/recurring-schedules/{id:\d+}', $route([RecurringSchedulesController::class, 'update'], [RequireAdmin::class]));
        $r->addRoute('DELETE', '/recurring-schedules/{id:\d+}', $route([RecurringSchedulesController::class, 'destroy'], [RequireAdmin::class]));

        $r->addRoute('GET', '/admin/users', $route([AdminUsersController::class, 'index'], [RequireAdmin::class]));
        $r->addRoute('PATCH', '/admin/users/{id:\d+}', $route([AdminUsersController::class, 'update'], [RequireAdmin::class]));
    });

    // Pages: each is a PHP controller rendering resources/views/layout.php
    // around a page-specific Vue island (see resources/views/pages/*.php
    // and client/src/entries/*.ts). These literal routes must stay
    // registered before the catch-all below -- FastRoute throws if a
    // static route is added after a variable route that would shadow it.
    $r->addRoute('GET', '/login', $route([LoginPageController::class, 'show']));
    $r->addRoute('POST', '/logout', $route([LogoutController::class, 'store']));
    $r->addRoute('GET', '/blocks', $route([BlocksPageController::class, 'show']));
    $r->addRoute('GET', '/calendar', $route([CalendarPageController::class, 'show']));
    $r->addRoute('GET', '/admin', $route([AdminPageController::class, 'show']));
    $r->addRoute('GET', '/', $route([BlocksPageController::class, 'show']));

    // Built Vue asset bundles (client/, built into storage/frontend/,
    // outside the web root -- never served directly by Caddy).
    $r->addRoute('GET', '/assets/{path:.+}', $route([FrontendController::class, 'show']));
    $r->addRoute('GET', '/favicon.ico', $route([FrontendController::class, 'show']));

    // Catch-all: any other non-/api path renders an HTML 404 page. Keeps
    // the (?!api/) exclusion so unmatched /api/* requests still fall
    // through to RouteDispatcher's JSON 404/405 handling.
    $r->addRoute('GET', '/{path:(?!api/).*}', $route([NotFoundController::class, 'show']));
};

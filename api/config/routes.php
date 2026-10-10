<?php

return static function (\League\Route\Router $r): void {
    $r->group('', function (\League\Route\RouteGroup $group) {
        $group->get('/me', \Schedule\Http\Controllers\MeController::class);
    })
        ->lazyMiddleware(\Schedule\Http\Middleware\SessionMiddleware::class);

    $r->group('/auth', function (\League\Route\RouteGroup $group) {
        $group->post('/login', \Schedule\Http\Controllers\Auth\LoginController::class);
        $group->post('/logout', \Schedule\Http\Controllers\Auth\LogoutController::class)
            ->lazyMiddleware(\Schedule\Http\Middleware\SessionMiddleware::class);
    });

    $r->lazyMiddleware(\Schedule\Http\Middleware\JsonParseMiddleware::class);
};

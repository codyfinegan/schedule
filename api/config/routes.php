<?php

return static function (\League\Route\Router $r): void {
    $r->group('', function (\League\Route\RouteGroup $group) {
        $group->get('/me', \Schedule\Http\Controllers\MeController::class);
    })->lazyMiddleware(\Schedule\Http\Middleware\SessionMiddleware::class);
};

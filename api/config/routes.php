<?php

return static function (\League\Route\Router $r): void {
    $r->get('/me', \Schedule\Http\Controllers\MeController::class);
};

<?php

use Schedule\Http\Middleware\AuthenticateSession;
use Schedule\Http\Middleware\HandleExceptions;

return [
    HandleExceptions::class,
    AuthenticateSession::class,
];

<?php

declare(strict_types=1);

namespace Schedule\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Schedule\Http\Exceptions\HttpException;
use Schedule\Models\User;

final class RequireAdmin implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user instanceof User) {
            throw HttpException::unauthorized();
        }

        if (!$user->is_admin) {
            throw HttpException::forbidden('Admin access required.');
        }

        return $handler->handle($request);
    }
}

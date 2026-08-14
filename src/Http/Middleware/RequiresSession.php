<?php

declare(strict_types=1);

namespace Schedule\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Schedule\Http\Exceptions\HttpException;
use Schedule\Models\Session;

final class RequiresSession implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request->getAttribute('session') instanceof Session) {
            throw HttpException::unauthorized('A valid session is required.');
        }

        return $handler->handle($request);
    }
}

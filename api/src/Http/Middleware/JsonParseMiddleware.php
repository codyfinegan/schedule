<?php

declare(strict_types=1);

namespace Schedule\Http\Middleware;

use League\Route\Http\Exception\BadRequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class JsonParseMiddleware implements MiddlewareInterface
{
    /**
     * @throws BadRequestException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if ($contentType === '') {
            return $handler->handle($request);
        }

        $contentType = explode(';', $contentType)[0]
                |> trim(...)
                |> strtolower(...);

        if ($contentType !== 'application/json') {
            return $handler->handle($request);
        }

        $body = (string)$request->getBody();
        if ($body === '') {
            return $handler->handle($request->withParsedBody([]));
        }

        try {
            $payload = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new BadRequestException($e->getMessage());
        }

        if (!is_array($payload)) {
            throw new BadRequestException('Invalid Payload');
        }

        return $handler->handle($request->withParsedBody($payload));
    }
}

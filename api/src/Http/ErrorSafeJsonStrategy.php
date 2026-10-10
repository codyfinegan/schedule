<?php

declare(strict_types=1);

namespace Schedule\Http;

use League\Route\Http\Exception as HttpException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class ErrorSafeJsonStrategy extends \League\Route\Strategy\JsonStrategy
{
    #[Override]
    public function getThrowableHandler(): MiddlewareInterface
    {
        return new class ($this->responseFactory->createResponse()) implements MiddlewareInterface {
            public function __construct(protected readonly ResponseInterface $response)
            {
            }

            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler,
            ): ResponseInterface {
                try {
                    return $handler->handle($request);
                } catch (Throwable $exception) {
                    $response = $this->response;

                    if ($exception instanceof HttpException) {
                        return $exception->buildJsonResponse($response);
                    }

                    error_log((string)$exception);
                    $response->getBody()->write('{"status_code": 500, "reason_phrase": "Internal Server Error"}');
                    $response = $response->withAddedHeader('content-type', 'application/json');
                    return $response->withStatus(500);
                }
            }
        };
    }
}

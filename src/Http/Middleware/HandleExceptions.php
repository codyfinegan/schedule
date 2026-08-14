<?php

declare(strict_types=1);

namespace Schedule\Http\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Schedule\Config;
use Schedule\Http\Exceptions\HttpException;
use Throwable;

final class HandleExceptions implements MiddlewareInterface
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpException $e) {
            $payload = ['message' => $e->getMessage()];
            if ($e->errors !== []) {
                $payload['errors'] = $e->errors;
            }

            return $this->jsonResponse($e->status, $payload);
        } catch (Throwable $e) {
            $payload = ['message' => 'Internal server error.'];
            if ($this->config->debug) {
                $payload['exception'] = $e::class;
                $payload['detail'] = $e->getMessage();
                $payload['trace'] = explode("\n", $e->getTraceAsString());
            }

            return $this->jsonResponse(500, $payload);
        }
    }

    private function jsonResponse(int $status, array $payload): ResponseInterface
    {
        return new Response(
            $status,
            ['Content-Type' => 'application/json'],
            Stream::create(json_encode($payload)),
        );
    }
}

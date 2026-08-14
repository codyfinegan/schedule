<?php

declare(strict_types=1);

namespace Schedule\Http;

use DI\Container;
use FastRoute\Dispatcher;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Relay\Relay;
use Schedule\Http\Exceptions\HttpException;

/**
 * Terminal PSR-15 handler for the outer (global) middleware queue: runs
 * FastRoute, then builds and runs a second, inner Relay queue made up of
 * the matched route's own middleware (e.g. RequireAdmin) around the
 * controller invocation. This is what lets per-route middleware exist
 * without putting admin-only checks in the global queue.
 */
final class RouteDispatcher implements RequestHandlerInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
        private readonly Container $container,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $path = rawurldecode($request->getUri()->getPath());
        $routeInfo = $this->dispatcher->dispatch($request->getMethod(), $path);

        return match ($routeInfo[0]) {
            Dispatcher::NOT_FOUND => throw HttpException::notFound(),
            Dispatcher::METHOD_NOT_ALLOWED => throw new HttpException(405, 'Method not allowed.'),
            Dispatcher::FOUND => $this->dispatchFound($routeInfo[1], $routeInfo[2], $request),
        };
    }

    private function dispatchFound(array $routeData, array $vars, ServerRequestInterface $request): ResponseInterface
    {
        foreach ($vars as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        $queue = [];
        foreach ($routeData['middleware'] ?? [] as $middlewareClass) {
            $queue[] = $this->container->get($middlewareClass);
        }
        $queue[] = new ControllerRequestHandler($this->container, $routeData['handler'], $vars);

        return (new Relay($queue))->handle($request);
    }
}

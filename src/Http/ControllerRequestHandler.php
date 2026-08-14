<?php

declare(strict_types=1);

namespace Schedule\Http;

use DI\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Terminal PSR-15 handler that invokes a route's [Controller::class, 'method']
 * callable, autowiring the controller and passing route params + the PSR-7
 * request through by name via the DI container's call().
 */
final class ControllerRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly Container $container,
        private readonly array $controllerHandler,
        private readonly array $routeParams,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->container->call($this->controllerHandler, [
            'request' => $request,
            ...$this->routeParams,
        ]);
    }
}

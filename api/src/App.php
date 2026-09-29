<?php

declare(strict_types=1);

namespace Schedule;


use League\Route\Router;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Schedule\Http\ResponseEmitter;


final class App implements RequestHandlerInterface
{

    public function __construct(
        private ContainerInterface $container,
        private Router $router,
        private ServerRequestCreator $requestCreator,
        private ResponseEmitter $emitter,
    ) {
    }

    public function container(): ContainerInterface
    {
        return $this->container;
    }

    public function run(): void
    {
        $request = $this->requestCreator->fromGlobals();
        $response = $this->handle($request);
        $this->emitter->emit($response);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->router->dispatch($request);
    }
}

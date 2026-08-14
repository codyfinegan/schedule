<?php

declare(strict_types=1);

namespace Schedule\Http;

use DI\Container;
use FastRoute\Dispatcher;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseInterface;
use Relay\Relay;

final class Kernel
{
    public function __construct(
        private readonly Container $container,
        private readonly Dispatcher $dispatcher,
    ) {
    }

    public function handle(): void
    {
        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $request = $creator->fromGlobals();

        $queue = array_map(
            fn (string $middlewareClass) => $this->container->get($middlewareClass),
            $this->container->get('http.middleware'),
        );
        $queue[] = $this->container->get(RouteDispatcher::class);

        $response = (new Relay($queue))->handle($request);

        $this->emit($response);
    }

    private function emit(ResponseInterface $response): void
    {
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        $body = $response->getBody();
        $body->rewind();
        while (!$body->eof()) {
            echo $body->read(8192);
        }
    }
}

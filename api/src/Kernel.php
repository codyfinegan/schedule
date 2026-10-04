<?php

declare(strict_types=1);

namespace Schedule;

use DI\Container;
use DI\ContainerBuilder;
use League\Route\Router;
use League\Route\Strategy\JsonStrategy;
use Psr\Container\ContainerInterface;

final class Kernel
{
    public function __construct(
        private readonly string $baseDir,
    ) {
    }

    public function boot(): App
    {
        $container = $this->container();

        $strategy = $container->get(JsonStrategy::class);
        $strategy->setContainer($container);

        $router = new Router();
        $router->setStrategy($strategy);
//        $router->lazyMiddlewares();

        (require $this->baseDir . '/config/routes.php')($router);

        $container->set(Router::class, $router);
        $container->set(ContainerInterface::class, $container);

        return $container->get(App::class);
    }

    /**
     * Build just the DI container, without the HTTP stack, for CLI scripts.
     *
     * @return Container
     */
    public function container(): Container
    {
        $config = Config::fromEnv(getenv());

        return new ContainerBuilder()
            ->addDefinitions([Config::class => $config])
            ->addDefinitions($this->baseDir . '/config/container.php')
            ->build();
    }
}

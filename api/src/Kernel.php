<?php

declare(strict_types=1);

namespace Schedule;


use DI\ContainerBuilder;
use League\Route\Router;
use League\Route\Strategy\JsonStrategy;
use Psr\Container\ContainerInterface;

final class Kernel
{

    public function __construct(
        private string $baseDir,
    ) {
    }

    public function boot()
    {
        $config = Config::fromEnv(getenv());
        $container = new ContainerBuilder()
            ->addDefinitions([Config::class => $config])
            ->addDefinitions($this->baseDir . '/config/container.php')
            ->build();

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
}
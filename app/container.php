<?php

use DI\Container;
use FastRoute\Dispatcher;
use Illuminate\Database\Capsule\Manager;
use Phinx\Console\PhinxApplication;
use Symfony\Component\Console\Application;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Schedule\Config;
use Schedule\Http\Controllers\Public\Pages\FrontendController;
use Schedule\View\TemplateRenderer;
use Schedule\View\ViteManifest;

use function DI\factory;
use function FastRoute\simpleDispatcher;

return [
    // Config
    Config::class => factory(fn () => new Config(__DIR__ . '/config.php')),

    // Database
    Manager::class => factory(function (Container $container) {
        $capsule = new Manager();

        $config = $container->get(Config::class);
        $db = $config->db;

        $capsule->addConnection([
            'driver' => $db['driver'],
            'database' => $db['database'],
            'prefix' => $db['prefix'],
        ]);

        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        return $capsule;
    }),

    // Mail
    MailerInterface::class => factory(function (Container $container) {
        $config = $container->get(Config::class);
        $transport = Transport::fromDsn($config->get('mail.dsn'));

        return new Mailer($transport);
    }),

    // Frontend
    FrontendController::class => factory(
        fn (Container $container) => new FrontendController($container->get(Config::class)->get('frontend.dist_path')),
    ),

    // Views
    ViteManifest::class => factory(function (Container $container) {
        $config = $container->get(Config::class);

        return new ViteManifest($config->get('frontend.dist_path'), $config->get('vite.dev_server_url'));
    }),
    TemplateRenderer::class => factory(
        fn (Container $container) => new TemplateRenderer(__DIR__ . '/../resources/views', $container->get(ViteManifest::class)),
    ),

    // Routing
    Dispatcher::class => factory(fn () => simpleDispatcher(require __DIR__ . '/routes.php')),
    'http.middleware' => factory(fn () => require __DIR__ . '/http_middleware.php'),

    // Console
    Application::class => factory(function (Container $container) {
        $console = new Application('schedule');
        $console->setAutoExit(false);

        foreach (require __DIR__ . '/commands.php' as $commandClass) {
            $console->addCommand($container->get($commandClass));
        }

        $phinx = new PhinxApplication();
        $phinxCommands = array_filter(
            $phinx->all(),
            fn ($command) => str_starts_with($command::class, 'Phinx\\'),
        );
        $console->addCommands($phinxCommands);

        return $console;
    }),
];

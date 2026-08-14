<?php

use Symfony\Component\Console\Application;

/** @var \DI\Container $container */
$container = require __DIR__ . '/app/bootstrap.php';

/** @var Application $application */
$application = $container->get(Application::class);

exit($application->run());

<?php

/**
 * Builds and returns the DI container. Used by both public/index.php
 * (web) and console.php (CLI).
 */

use DI\ContainerBuilder;
use Illuminate\Database\Capsule\Manager;

require __DIR__ . '/../vendor/autoload.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/container.php');
$containerBuilder->useAutowiring(true);

$container = $containerBuilder->build();

// Boots Eloquent's global connection resolver unconditionally, since not
// every controller/command depends on Manager directly (most use Eloquent's
// static query methods instead).
$container->get(Manager::class);

return $container;

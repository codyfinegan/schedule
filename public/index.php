<?php

use Schedule\Http\Kernel;

/** @var \DI\Container $container */
$container = require __DIR__ . '/../app/bootstrap.php';

$container->get(Kernel::class)->handle();

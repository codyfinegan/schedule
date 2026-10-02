<?php

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Schedule\Config;
use Schedule\Database\Database;
use Schedule\Database\Factory;

return [
    RequestFactoryInterface::class => \DI\create(Psr17Factory::class),
    ResponseFactoryInterface::class => \DI\get(RequestFactoryInterface::class),
    ServerRequestFactoryInterface::class => \DI\get(RequestFactoryInterface::class),
    StreamFactoryInterface::class => \DI\get(RequestFactoryInterface::class),
    UploadedFileFactoryInterface::class => \DI\get(RequestFactoryInterface::class),
    UriFactoryInterface::class => \DI\get(RequestFactoryInterface::class),

    Database::class => fn(Config $config) => Factory::create(
        $config->database_path
    ),
];
<?php

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;

return [
    RequestFactoryInterface::class => \DI\create(Psr17Factory::class),
    ResponseFactoryInterface::class => \DI\get(RequestFactoryInterface::class),
    ServerRequestFactoryInterface::class => \DI\get(RequestFactoryInterface::class),
    StreamFactoryInterface::class => \DI\get(RequestFactoryInterface::class),
    UploadedFileFactoryInterface::class => \DI\get(RequestFactoryInterface::class),
    UriFactoryInterface::class => \DI\get(RequestFactoryInterface::class),
];
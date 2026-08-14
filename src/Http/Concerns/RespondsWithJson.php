<?php

declare(strict_types=1);

namespace Schedule\Http\Concerns;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

trait RespondsWithJson
{
    protected function json(mixed $data, int $status = 200): ResponseInterface
    {
        $body = Stream::create(json_encode($data, JSON_THROW_ON_ERROR));

        return new Response($status, ['Content-Type' => 'application/json'], $body);
    }

    protected function noContent(): ResponseInterface
    {
        return new Response(204);
    }
}

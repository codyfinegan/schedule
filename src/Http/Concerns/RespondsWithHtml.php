<?php

declare(strict_types=1);

namespace Schedule\Http\Concerns;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;

trait RespondsWithHtml
{
    protected function html(string $body, int $status = 200): ResponseInterface
    {
        return new Response($status, ['Content-Type' => 'text/html; charset=utf-8'], Stream::create($body));
    }
}

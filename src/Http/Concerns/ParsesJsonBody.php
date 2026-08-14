<?php

declare(strict_types=1);

namespace Schedule\Http\Concerns;

use Psr\Http\Message\ServerRequestInterface;

trait ParsesJsonBody
{
    protected function body(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        if (is_array($parsed) && $parsed !== []) {
            return $parsed;
        }

        $raw = (string) $request->getBody();
        if ($raw === '') {
            return [];
        }

        return json_decode($raw, true) ?? [];
    }
}

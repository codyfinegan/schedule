<?php
declare(strict_types=1);

namespace Schedule\Http\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class MeController
{
    public function __invoke(ServerRequestInterface $request, array $args): array|ResponseInterface
    {
        return [
            'hi' => 'uyes'
        ];
    }
}

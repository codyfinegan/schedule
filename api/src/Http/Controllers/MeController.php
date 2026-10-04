<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Resources\SessionResource;
use Schedule\Models\Session;

final class MeController
{
    /**
     * @param array<string, string> $args
     */
    public function __invoke(ServerRequestInterface $request, array $args): SessionResource
    {
        $session = Session::fromRequest($request);
        return SessionResource::fromSession($session);
    }
}

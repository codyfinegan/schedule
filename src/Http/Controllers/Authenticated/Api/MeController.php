<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Authenticated\Api;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Concerns\RequiresUser;
use Schedule\Http\Concerns\RespondsWithJson;
use Schedule\Http\Resources\UserResource;

final class MeController
{
    use RespondsWithJson;
    use RequiresUser;

    public function show(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->currentUser($request);

        return $this->json(['user' => UserResource::make($user)]);
    }
}

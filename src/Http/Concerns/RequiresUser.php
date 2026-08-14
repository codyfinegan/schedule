<?php

declare(strict_types=1);

namespace Schedule\Http\Concerns;

use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Exceptions\HttpException;
use Schedule\Models\User;

trait RequiresUser
{
    protected function currentUser(ServerRequestInterface $request): User
    {
        $user = $request->getAttribute('user');
        if (!$user instanceof User) {
            throw HttpException::unauthorized();
        }

        return $user;
    }
}

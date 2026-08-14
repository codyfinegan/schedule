<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Authenticated\Api;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Concerns\ParsesJsonBody;
use Schedule\Http\Concerns\RespondsWithJson;
use Schedule\Http\Exceptions\HttpException;
use Schedule\Http\Resources\UserResource;
use Schedule\Models\User;

final class AdminUsersController
{
    use RespondsWithJson;
    use ParsesJsonBody;

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->json(['data' => $this->listForPage()]);
    }

    /**
     * Same listing used by index(), reused by AdminPageController to seed
     * the admin page's initial data server-side.
     */
    public function listForPage(): array
    {
        return UserResource::collection(User::query()->orderBy('created_at')->get());
    }

    public function update(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $user = User::query()->find((int) $id);
        if ($user === null) {
            throw HttpException::notFound('User not found.');
        }

        $body = $this->body($request);

        if (array_key_exists('is_approved', $body)) {
            $user->is_approved = (bool) $body['is_approved'];
        }
        if (array_key_exists('is_admin', $body)) {
            $user->is_admin = (bool) $body['is_admin'];
        }
        if (array_key_exists('display_name', $body)) {
            $user->display_name = $body['display_name'];
        }

        $user->save();

        return $this->json(['data' => UserResource::make($user)]);
    }
}

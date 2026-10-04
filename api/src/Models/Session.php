<?php

declare(strict_types=1);

namespace Schedule\Models;

use Psr\Http\Message\ServerRequestInterface;

final class Session
{
    public function __construct(
        protected(set) string $tokenHash,
        protected(set) string $csrfToken,
        protected(set) int $createdAt,
        protected(set) int $lastSeenAt,
        protected(set) int $expiresAt,
        protected(set) User $user,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row, string $prefix = '', string $userPrefix = 'user__'): Session
    {
        if (!isset($row[$userPrefix . 'id'])) {
            throw new \LogicException('User prefix missing');
        }
        $user = User::fromRow($row, $userPrefix);

        return new self(
            tokenHash: $row[$prefix . 'token_hash'],
            csrfToken: $row[$prefix . 'csrf_token'],
            createdAt: $row[$prefix . 'created_at'],
            lastSeenAt: $row[$prefix . 'last_seen_at'],
            expiresAt: $row[$prefix . 'expires_at'],
            user: $user,
        );
    }

    public static function fromRequest(ServerRequestInterface $request): Session
    {
        $session = $request->getAttribute(Session::class);
        if (!$session instanceof Session) {
            throw new \LogicException('Route is missing SessionMiddleware');
        }

        return $session;
    }

    public function touched(int $lastSeenAt, int $expiresAt): Session
    {
        return clone($this, ['lastSeenAt' => $lastSeenAt, 'expiresAt' => $expiresAt]);
    }
}

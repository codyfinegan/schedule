<?php

declare(strict_types=1);

namespace Schedule\Models;

use Schedule\Contracts\IEntity;

final class Session implements IEntity
{

    public function __construct(
        protected(set) string $token_hash,
        protected(set) int $user_id,
        protected(set) string $csrf_token,
        protected(set) int $created_at,
        protected(set) int $last_seen_at,
        protected(set) int $expires_at,
    ) {
    }

    public static function fromRow(array $row): Session
    {
        return new self(
            $row['token_hash'],
            $row['user_id'],
            $row['csrf_token'],
            $row['created_at'],
            $row['last_seen_at'],
            $row['expires_at'],
        );
    }

    public function touched(int $lastSeenAt, int $expiresAt): Session
    {
        return clone($this, ['last_seen_at' => $lastSeenAt, 'expires_at' => $expiresAt]);
    }
}

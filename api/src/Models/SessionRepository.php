<?php

declare(strict_types=1);

namespace Schedule\Models;

use Schedule\Database\Database;

final class SessionRepository
{
    private const int TOUCH_INTERVAL = 60 * 60;
    private const int IDLE_TTL = 60 * 60 * 24 * 30;
    private const int MAX_LIFETIME = 60 * 60 * 24 * 365;

    public function __construct(
        private readonly Database $database,
    ) {
    }

    public function findValid(string $hash, int $now): ?Session
    {
        $statement = $this->database->prepare('SELECT * FROM sessions WHERE token_hash = :hash AND expires_at > :now');
        $statement->execute(['hash' => $hash, 'now' => $now]);
        $row = $statement->fetch();
        return $row ? Session::fromRow($row) : null;
    }

    public function touch(Session $session, int $now): Session
    {
        if ($now - $session->last_seen_at < self::TOUCH_INTERVAL) {
            return $session;
        }

        $statement = $this->database->prepare(
            '
            UPDATE sessions SET 
                last_seen_at = :now,
                expires_at = :expires_at
            WHERE token_hash = :hash'
        );
        $row = [
            'now' => $now,
            'hash' => $session->token_hash,
            'expires_at' => min($session->created_at + self::MAX_LIFETIME, $now + self::IDLE_TTL),
        ];
        $statement->execute($row);
        return $session->touched($row['now'], $row['expires_at']);
    }

    public function create(
        int $user_id,
        int $now,
    ): NewSession {
        $token = $this->generateToken();
        $csrf_token = $this->generateToken();
        $hash_token = hash('sha256', $token);

        $statement = $this->database->prepare(
            '
            INSERT INTO sessions (token_hash, user_id, csrf_token, created_at, last_seen_at, expires_at)
                VALUES (:token_hash, :user_id, :csrf_token, :now, :now, :expires_at)'
        );
        $expires_at = $now + self::IDLE_TTL;
        $row = [
            'token_hash' => $hash_token,
            'user_id' => $user_id,
            'csrf_token' => $csrf_token,
            'now' => $now,
            'expires_at' => $expires_at,
        ];
        $statement->execute($row);

        $session = new Session(
            token_hash: $hash_token, user_id: $user_id, csrf_token: $csrf_token,
            created_at: $now, last_seen_at: $now, expires_at: $expires_at,
        );
        return new NewSession($session, $token);
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

}

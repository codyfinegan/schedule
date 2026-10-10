<?php

declare(strict_types=1);

namespace Schedule\Models;

use Schedule\Database\Database;

final class SessionRepository
{
    public const int TOUCH_INTERVAL = 60 * 60;
    public const int IDLE_TTL = 60 * 60 * 24 * 30;
    public const int MAX_LIFETIME = 60 * 60 * 24 * 365;

    public function __construct(
        private readonly Database $database,
    ) {
    }

    public function findValid(string $hash, int $now): ?Session
    {
        $statement = $this->database->prepare(
            '
            SELECT s.token_hash, s.csrf_token, s.created_at, s.last_seen_at, s.expires_at,
                   u.id AS user__id,
                   u.email AS user__email,
                   u.name AS user__name,
                   u.is_admin AS user__is_admin,
                   u.created_at AS user__created_at,
                   u.last_login_at AS user__last_login_at
            FROM sessions s
            JOIN users u ON u.id = s.user_id
            WHERE s.token_hash = :hash 
              AND s.expires_at > :now
        '
        );
        $statement->execute(['hash' => $hash, 'now' => $now]);
        $row = $statement->fetch();
        if (!$row) {
            return null;
        }
        return Session::fromRow($row);
    }

    public function touch(Session $session, int $now): Session
    {
        if ($now - $session->lastSeenAt < self::TOUCH_INTERVAL) {
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
            'hash' => $session->tokenHash,
            'expires_at' => min($session->createdAt + self::MAX_LIFETIME, $now + self::IDLE_TTL),
        ];
        $statement->execute($row);
        return $session->touched($row['now'], $row['expires_at']);
    }

    public function create(
        User $user,
        int $now,
    ): NewSession {
        $token = $this->generateToken();
        $csrfToken = $this->generateToken();
        $hashToken = hash('sha256', $token);

        $statement = $this->database->prepare(
            '
            INSERT INTO sessions (token_hash, user_id, csrf_token, created_at, last_seen_at, expires_at)
                VALUES (:token_hash, :user_id, :csrf_token, :now, :now, :expires_at)'
        );
        $expiresAt = $now + self::IDLE_TTL;
        $row = [
            'token_hash' => $hashToken,
            'user_id' => $user->id,
            'csrf_token' => $csrfToken,
            'now' => $now,
            'expires_at' => $expiresAt,
        ];
        $statement->execute($row);

        $session = new Session(
            tokenHash: $hashToken,
            csrfToken: $csrfToken,
            createdAt: $now,
            lastSeenAt: $now,
            expiresAt: $expiresAt,
            user: $user,
        );
        return new NewSession($session, $token);
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function destroy(Session $session): void
    {
        $statement = $this->database->prepare('DELETE FROM sessions WHERE token_hash = :hash');
        $row = [
            'hash' => $session->tokenHash,
        ];
        $statement->execute($row);
    }
}

<?php

declare(strict_types=1);

namespace Schedule\Services;

use Schedule\Database\Database;

final class LoginThrottle
{
    public const int WINDOW = 15 * 60;
    private const int MAX_PER_EMAIL = 5;
    private const int MAX_PER_IP = 20;

    public function __construct(
        private Database $database,
    ) {
    }

    public function tooManyAttempts(string $email, ?string $ip, int $now): bool
    {
        if ($this->count($this->emailKey($email), $now) >= self::MAX_PER_EMAIL) {
            return true;
        }

        return $ip !== null && $this->count($this->ipKey($ip), $now) >= self::MAX_PER_IP;
    }

    private function count(string $key, int $now): int
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(*) FROM login_attempts WHERE key = :key AND attempted_at > :since'
        );
        $statement->execute(['key' => $key, 'since' => $now - self::WINDOW]);

        return (int)$statement->fetchColumn();
    }

    private function emailKey(string $email): string
    {
        return hash('sha256', 'email:' . strtolower(trim($email)));
    }

    private function ipKey(string $ip): string
    {
        return hash('sha256', 'ip:' . $ip);
    }

    public function clear(string $email): void
    {
        $this->database
            ->prepare('DELETE FROM login_attempts WHERE key = :key')
            ->execute(['key' => $this->emailKey($email)]);
    }

    public function prune(int $now): void
    {
        $this->database
            ->prepare('DELETE FROM login_attempts WHERE attempted_at <= :cutoff')
            ->execute(['cutoff' => $now - self::WINDOW]);
    }

    public function recordFailure(int $now, ?string $email = null, ?string $ip = null): void
    {
        if ($email) {
            $this->record($this->emailKey($email), $now);
        }
        if ($ip) {
            $this->record($this->ipKey($ip), $now);
        }
    }

    private function record(string $key, int $now): void
    {
        $statement = $this->database->prepare(
            'INSERT INTO login_attempts (key, attempted_at) VALUES (:key, :attempted_at)'
        );
        $row = [
            'key' => $key,
            'attempted_at' => $now,
        ];
        $statement->execute($row);
    }
}

<?php

declare(strict_types=1);

namespace Schedule\Models;

use Schedule\Database\Database;

final class UserRepository
{
    // Nonya
    private const string FIXED_DUMMY_HASH = '$2y$12$eHq8UTbgxF50.er3Tem9N.9DqN/MWkeC9irB9xZMrlMpu/5iOy912';

    public function __construct(
        private readonly Database $database,
    ) {
    }

    public function findById(int $id): ?User
    {
        $statement = $this->database->prepare(
            '
            SELECT u.id AS user__id,
                   u.email AS user__email,
                   u.name AS user__name,
                   u.is_admin AS user__is_admin,
                   u.created_at AS user__created_at,
                   u.last_login_at AS user__last_login_at
            FROM users u
            WHERE u.id = :id 
        '
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!$row) {
            return null;
        }
        return User::fromRow($row, 'user__');
    }

    /**
     * @return array{id: int, password_hash: string}
     */
    public function findCredentialsByEmail(string $email): array
    {
        $statement = $this->database->prepare(
            '
            SELECT u.id AS user__id,
                   u.password_hash AS user__password_hash
            FROM users u
            WHERE u.email = :email
        '
        );
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();
        if (!$row || $row['user__password_hash'] === null) {
            $row = [
                'user__id' => -1,
                'user__password_hash' => self::FIXED_DUMMY_HASH,
            ];
        }

        return [
            'id' => $row['user__id'],
            'password_hash' => $row['user__password_hash'],
        ];
    }

    public function updateHash(int $userId, string $password): void
    {
        $statement = $this->database->prepare(
            'UPDATE users SET password_hash = :password_hash WHERE id = :id'
        );
        $row = [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $userId,
        ];
        $statement->execute($row);
    }

    public function touchUserLastLoginAt(int $userId): void
    {
        $statement = $this->database->prepare(
            'UPDATE users SET last_login_at = :now WHERE id = :id'
        );
        $row = [
            'now' => time(),
            'id' => $userId,
        ];
        $statement->execute($row);
    }
}

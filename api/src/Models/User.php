<?php

declare(strict_types=1);

namespace Schedule\Models;

final class User implements \JsonSerializable
{
    public function __construct(
        protected(set) int $id,
        protected(set) string $email,
        protected(set) ?string $name,
        protected(set) bool $isAdmin,
        protected(set) int $createdAt,
        protected(set) ?int $lastLoginAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row, string $prefix = ''): User
    {
        return new self(
            id: $row[$prefix . 'id'],
            email: $row[$prefix . 'email'],
            name: $row[$prefix . 'name'],
            isAdmin: (bool)$row[$prefix . 'is_admin'],
            createdAt: $row[$prefix . 'created_at'],
            lastLoginAt: $row[$prefix . 'last_login_at'],
        );
    }

    public function jsonSerialize(): never
    {
        throw new \LogicException('Serialize a User through a response DTO');
    }
}

<?php

declare(strict_types=1);

namespace Schedule\Http\Resources;

use Schedule\Models\User;

final readonly class CurrentUserResource
{
    public function __construct(
        public int $id,
        public string $email,
        public ?string $name,
        public bool $isAdmin,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self($user->id, $user->email, $user->name, $user->isAdmin);
    }
}

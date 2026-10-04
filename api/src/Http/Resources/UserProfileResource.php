<?php

declare(strict_types=1);

namespace Schedule\Http\Resources;

use Schedule\Models\User;

final readonly class UserProfileResource
{
    public function __construct(
        public int $id,
        public ?string $name,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self($user->id, $user->name);
    }
}

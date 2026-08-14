<?php

declare(strict_types=1);

namespace Schedule\Http\Resources;

use Schedule\Models\User;

final class UserResource
{
    public static function make(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'display_name' => $user->display_name,
            'is_admin' => (bool) $user->is_admin,
            'is_approved' => (bool) $user->is_approved,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    public static function collection(iterable $users): array
    {
        return array_map(self::make(...), is_array($users) ? $users : iterator_to_array($users));
    }
}

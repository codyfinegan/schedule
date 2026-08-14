<?php

declare(strict_types=1);

namespace Schedule\Http\Resources;

use Schedule\Models\Rsvp;

final class RsvpResource
{
    public static function make(Rsvp $rsvp): array
    {
        return [
            'id' => $rsvp->id,
            'user' => UserResource::make($rsvp->user),
            'created_at' => $rsvp->created_at?->toIso8601String(),
        ];
    }

    public static function collection(iterable $rsvps): array
    {
        $result = [];
        foreach ($rsvps as $rsvp) {
            $result[] = self::make($rsvp);
        }

        return $result;
    }
}

<?php

declare(strict_types=1);

namespace Schedule\Http\Resources;

use Schedule\Models\GameBlock;
use Schedule\Models\User;

final class BlockResource
{
    public static function make(GameBlock $block, ?User $viewer = null, bool $withRsvps = false): array
    {
        $data = [
            'id' => $block->id,
            'starts_at' => $block->starts_at->toIso8601String(),
            'ends_at' => $block->ends_at->toIso8601String(),
            'botc_app_code' => $block->botc_app_code,
            'recurring_schedule_id' => $block->recurring_schedule_id,
            'is_past' => $block->isPast(),
            'rsvp_count' => $block->relationLoaded('rsvps') ? $block->rsvps->count() : $block->rsvps()->count(),
            'created_at' => $block->created_at?->toIso8601String(),
        ];

        if ($viewer !== null) {
            $data['viewer_has_rsvped'] = $block->relationLoaded('rsvps')
                ? $block->rsvps->contains('user_id', $viewer->id)
                : $block->rsvps()->where('user_id', $viewer->id)->exists();
        }

        if ($withRsvps) {
            $data['rsvps'] = RsvpResource::collection($block->rsvps()->with('user')->get());
        }

        return $data;
    }

    public static function collection(iterable $blocks, ?User $viewer = null): array
    {
        $result = [];
        foreach ($blocks as $block) {
            $result[] = self::make($block, $viewer);
        }

        return $result;
    }
}

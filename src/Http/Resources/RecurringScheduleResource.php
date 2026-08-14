<?php

declare(strict_types=1);

namespace Schedule\Http\Resources;

use Schedule\Models\RecurringSchedule;

final class RecurringScheduleResource
{
    public static function make(RecurringSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'day_of_week' => $schedule->day_of_week,
            'start_time' => $schedule->start_time,
            'duration_minutes' => $schedule->duration_minutes,
            'is_active' => (bool) $schedule->is_active,
            'created_by_user_id' => $schedule->created_by_user_id,
            'created_at' => $schedule->created_at?->toIso8601String(),
        ];
    }

    public static function collection(iterable $schedules): array
    {
        $result = [];
        foreach ($schedules as $schedule) {
            $result[] = self::make($schedule);
        }

        return $result;
    }
}

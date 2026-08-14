<?php

declare(strict_types=1);

namespace Schedule\Services;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Schedule\Config;
use Schedule\Models\GameBlock;
use Schedule\Models\RecurringSchedule;

/**
 * Eagerly materializes recurring_schedules rows into concrete game_blocks
 * rows, out to a rolling horizon. Shared by the recurring-schedule
 * create/activate API endpoint and the schedule:generate-blocks console
 * command so both paths stay in sync.
 */
final class BlockMaterializer
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    /**
     * @return int number of newly-created blocks
     */
    public function materialize(RecurringSchedule $schedule): int
    {
        $timezone = new DateTimeZone($this->config->timezone);
        $now = CarbonImmutable::now($timezone);
        $horizon = $now->addMonths((int) $this->config->get('recurring.horizon_months', 2));

        [$hour, $minute] = array_map('intval', explode(':', $schedule->start_time));

        // Find the first occurrence (today or later) of the schedule's day_of_week.
        $cursor = $now->startOfDay()->setTime($hour, $minute);
        $daysUntilTarget = ($schedule->day_of_week - $cursor->dayOfWeek + 7) % 7;
        $cursor = $cursor->addDays($daysUntilTarget);
        if ($cursor->lt($now)) {
            // Same day but the start time already passed -- jump to next week.
            $cursor = $cursor->addDays(7);
        }

        $created = 0;

        // Adding calendar days (not seconds) each iteration keeps the local
        // wall-clock start time stable across DST transitions.
        while ($cursor->lte($horizon)) {
            $startsAtUtc = $cursor->setTimezone('UTC');
            $endsAtUtc = $cursor->addMinutes($schedule->duration_minutes)->setTimezone('UTC');

            [$block, $wasCreated] = $this->firstOrCreateBlock($schedule, $startsAtUtc, $endsAtUtc);
            if ($wasCreated) {
                $created++;
            }

            $cursor = $cursor->addDays(7);
        }

        return $created;
    }

    /**
     * @return array{0: GameBlock, 1: bool}
     */
    private function firstOrCreateBlock(RecurringSchedule $schedule, CarbonImmutable $startsAt, CarbonImmutable $endsAt): array
    {
        $existing = GameBlock::query()
            ->where('recurring_schedule_id', $schedule->id)
            ->where('starts_at', $startsAt->format('Y-m-d H:i:s'))
            ->first();

        if ($existing !== null) {
            return [$existing, false];
        }

        $block = GameBlock::create([
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
            'recurring_schedule_id' => $schedule->id,
            'created_by_user_id' => $schedule->created_by_user_id,
        ]);

        return [$block, true];
    }
}

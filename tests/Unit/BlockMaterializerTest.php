<?php

declare(strict_types=1);

namespace Schedule\Tests\Unit;

use Schedule\Models\GameBlock;
use Schedule\Models\RecurringSchedule;
use Schedule\Services\BlockMaterializer;
use Schedule\Tests\TestCase;

final class BlockMaterializerTest extends TestCase
{
    public function test_materialize_fills_the_two_month_rolling_horizon(): void
    {
        $user = $this->makeUser();
        $schedule = RecurringSchedule::create([
            'day_of_week' => 3, // Wednesday
            'start_time' => '19:00',
            'duration_minutes' => 180,
            'is_active' => true,
            'created_by_user_id' => $user->id,
        ]);

        $created = $this->container()->get(BlockMaterializer::class)->materialize($schedule);

        // ~2 months of weekly occurrences: at least 7, at most 10.
        self::assertGreaterThanOrEqual(7, $created);
        self::assertLessThanOrEqual(10, $created);
        self::assertSame($created, GameBlock::query()->where('recurring_schedule_id', $schedule->id)->count());

        foreach (GameBlock::query()->where('recurring_schedule_id', $schedule->id)->get() as $block) {
            self::assertSame(3, (int) $block->starts_at->copy()->timezone('America/New_York')->dayOfWeek);
            self::assertSame(
                180,
                (int) $block->starts_at->diffInMinutes($block->ends_at),
            );
        }
    }

    public function test_materialize_is_idempotent_and_never_duplicates_blocks(): void
    {
        $user = $this->makeUser();
        $schedule = RecurringSchedule::create([
            'day_of_week' => 5,
            'start_time' => '20:30',
            'duration_minutes' => 120,
            'is_active' => true,
            'created_by_user_id' => $user->id,
        ]);

        $materializer = $this->container()->get(BlockMaterializer::class);

        $firstRun = $materializer->materialize($schedule);
        $secondRun = $materializer->materialize($schedule);

        self::assertGreaterThan(0, $firstRun);
        self::assertSame(0, $secondRun);
        self::assertSame($firstRun, GameBlock::query()->where('recurring_schedule_id', $schedule->id)->count());
    }
}

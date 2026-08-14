<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Authenticated\Api;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Concerns\ParsesJsonBody;
use Schedule\Http\Concerns\RequiresUser;
use Schedule\Http\Concerns\RespondsWithJson;
use Schedule\Http\Exceptions\HttpException;
use Schedule\Http\Resources\RecurringScheduleResource;
use Schedule\Models\RecurringSchedule;
use Schedule\Services\BlockMaterializer;

final class RecurringSchedulesController
{
    use RespondsWithJson;
    use RequiresUser;
    use ParsesJsonBody;

    public function __construct(
        private readonly BlockMaterializer $materializer,
    ) {
    }

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $this->currentUser($request);

        return $this->json(['data' => $this->listForPage()]);
    }

    /**
     * Same listing used by index(), reused by AdminPageController to seed
     * the admin page's initial data server-side.
     */
    public function listForPage(): array
    {
        $schedules = RecurringSchedule::query()->orderBy('day_of_week')->orderBy('start_time')->get();

        return RecurringScheduleResource::collection($schedules);
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->currentUser($request);
        $body = $this->body($request);

        $this->validate($body);

        $schedule = RecurringSchedule::create([
            'day_of_week' => (int) $body['day_of_week'],
            'start_time' => $body['start_time'],
            'duration_minutes' => (int) $body['duration_minutes'],
            'is_active' => $body['is_active'] ?? true,
            'created_by_user_id' => $user->id,
        ]);

        if ($schedule->is_active) {
            $this->materializer->materialize($schedule);
        }

        return $this->json(['data' => RecurringScheduleResource::make($schedule)], 201);
    }

    public function update(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $this->currentUser($request);
        $schedule = $this->findOrFail($id);
        $body = $this->body($request);

        $wasActive = (bool) $schedule->is_active;

        if (array_key_exists('day_of_week', $body)) {
            $schedule->day_of_week = (int) $body['day_of_week'];
        }
        if (array_key_exists('start_time', $body)) {
            $schedule->start_time = $body['start_time'];
        }
        if (array_key_exists('duration_minutes', $body)) {
            $schedule->duration_minutes = (int) $body['duration_minutes'];
        }
        if (array_key_exists('is_active', $body)) {
            $schedule->is_active = (bool) $body['is_active'];
        }

        $schedule->save();

        // Re-materialize whenever the schedule (re)activates, so a newly
        // activated/edited schedule gets its horizon filled immediately
        // instead of waiting for the next cron run.
        if ($schedule->is_active && (!$wasActive || $schedule->wasChanged(['day_of_week', 'start_time', 'duration_minutes']))) {
            $this->materializer->materialize($schedule);
        }

        return $this->json(['data' => RecurringScheduleResource::make($schedule)]);
    }

    public function destroy(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $this->currentUser($request);
        $schedule = $this->findOrFail($id);
        $schedule->delete();

        return $this->noContent();
    }

    private function findOrFail(string $id): RecurringSchedule
    {
        $schedule = RecurringSchedule::query()->find((int) $id);
        if ($schedule === null) {
            throw HttpException::notFound('Recurring schedule not found.');
        }

        return $schedule;
    }

    private function validate(array $body): void
    {
        $dayOfWeek = $body['day_of_week'] ?? null;
        $startTime = $body['start_time'] ?? null;
        $duration = $body['duration_minutes'] ?? null;

        if (!is_numeric($dayOfWeek) || (int) $dayOfWeek < 0 || (int) $dayOfWeek > 6) {
            throw HttpException::unprocessable('day_of_week must be 0-6 (Sunday-Saturday).');
        }
        if (!is_string($startTime) || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime)) {
            throw HttpException::unprocessable('start_time must be HH:MM(:SS).');
        }
        if (!is_numeric($duration) || (int) $duration <= 0) {
            throw HttpException::unprocessable('duration_minutes must be a positive integer.');
        }
    }
}

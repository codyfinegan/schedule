<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Authenticated\Api;

use Carbon\CarbonImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Concerns\ParsesJsonBody;
use Schedule\Http\Concerns\RequiresUser;
use Schedule\Http\Concerns\RespondsWithJson;
use Schedule\Http\Exceptions\HttpException;
use Schedule\Http\Resources\BlockResource;
use Schedule\Models\GameBlock;
use Schedule\Models\Rsvp;
use Schedule\Models\User;

final class BlocksController
{
    use RespondsWithJson;
    use RequiresUser;
    use ParsesJsonBody;

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->currentUser($request);
        $query = $request->getQueryParams();

        $when = $query['when'] ?? 'upcoming';
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 20)));

        [$blocks, $total] = $this->fetchBlocks($when, $page, $perPage);

        return $this->json([
            'data' => BlockResource::collection($blocks, $user),
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $total],
        ]);
    }

    /**
     * Same listing used by index(), reused by Pages\* controllers to seed a
     * page's initial data server-side instead of the client fetching it on
     * mount.
     */
    public function listForPage(?User $viewer, string $when, int $page = 1, int $perPage = 20): array
    {
        [$blocks] = $this->fetchBlocks($when, $page, $perPage);

        return BlockResource::collection($blocks, $viewer);
    }

    private function fetchBlocks(string $when, int $page, int $perPage): array
    {
        $now = CarbonImmutable::now('UTC')->format('Y-m-d H:i:s');

        $builder = GameBlock::query()->with('rsvps');
        if ($when === 'past') {
            $builder->where('ends_at', '<', $now)->orderByDesc('starts_at');
        } else {
            $builder->where('ends_at', '>=', $now)->orderBy('starts_at');
        }

        $total = (clone $builder)->count();
        $blocks = $builder->forPage($page, $perPage)->get();

        return [$blocks, $total];
    }

    public function show(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $user = $this->currentUser($request);
        $block = $this->findOrFail($id);

        return $this->json(['data' => BlockResource::make($block, $user, withRsvps: true)]);
    }

    public function store(ServerRequestInterface $request): ResponseInterface
    {
        $user = $this->currentUser($request);
        $body = $this->body($request);

        [$startsAt, $endsAt] = $this->validateTimes($body);

        $block = GameBlock::create([
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'botc_app_code' => $body['botc_app_code'] ?? null,
            'created_by_user_id' => $user->id,
        ]);

        return $this->json(['data' => BlockResource::make($block, $user)], 201);
    }

    public function update(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $this->currentUser($request);
        $block = $this->findOrFail($id);

        if ($block->isPast()) {
            throw HttpException::conflict('Past blocks are read-only.');
        }

        $body = $this->body($request);

        if (array_key_exists('starts_at', $body) || array_key_exists('ends_at', $body)) {
            [$startsAt, $endsAt] = $this->validateTimes([
                'starts_at' => $body['starts_at'] ?? $block->starts_at->toIso8601String(),
                'ends_at' => $body['ends_at'] ?? $block->ends_at->toIso8601String(),
            ]);
            $block->starts_at = $startsAt;
            $block->ends_at = $endsAt;
        }

        if (array_key_exists('botc_app_code', $body)) {
            $block->botc_app_code = $body['botc_app_code'];
        }

        $block->save();

        return $this->json(['data' => BlockResource::make($block)]);
    }

    public function destroy(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $this->currentUser($request);
        $block = $this->findOrFail($id);

        if ($block->isPast()) {
            throw HttpException::conflict('Past blocks are read-only.');
        }

        $block->delete();

        return $this->noContent();
    }

    public function rsvp(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $user = $this->currentUser($request);
        $block = $this->findOrFail($id);

        if ($block->isPast()) {
            throw HttpException::conflict('This block is in the past and can no longer be RSVPed to.');
        }

        Rsvp::query()->firstOrCreate([
            'game_block_id' => $block->id,
            'user_id' => $user->id,
        ]);

        return $this->json(['data' => BlockResource::make($block->fresh('rsvps'), $user)]);
    }

    public function unrsvp(ServerRequestInterface $request, string $id): ResponseInterface
    {
        $user = $this->currentUser($request);
        $block = $this->findOrFail($id);

        if ($block->isPast()) {
            throw HttpException::conflict('This block is in the past and can no longer be RSVPed to.');
        }

        Rsvp::query()->where('game_block_id', $block->id)->where('user_id', $user->id)->delete();

        return $this->json(['data' => BlockResource::make($block->fresh('rsvps'), $user)]);
    }

    private function findOrFail(string $id): GameBlock
    {
        $block = GameBlock::query()->find((int) $id);
        if ($block === null) {
            throw HttpException::notFound('Block not found.');
        }

        return $block;
    }

    private function validateTimes(array $body): array
    {
        $startsAt = $body['starts_at'] ?? null;
        $endsAt = $body['ends_at'] ?? null;

        if (!is_string($startsAt) || !is_string($endsAt)) {
            throw HttpException::unprocessable('starts_at and ends_at are required.');
        }

        try {
            $starts = CarbonImmutable::parse($startsAt)->setTimezone('UTC');
            $ends = CarbonImmutable::parse($endsAt)->setTimezone('UTC');
        } catch (\Throwable) {
            throw HttpException::unprocessable('starts_at and ends_at must be valid dates.');
        }

        if ($ends->lte($starts)) {
            throw HttpException::unprocessable('ends_at must be after starts_at.');
        }

        return [$starts->format('Y-m-d H:i:s'), $ends->format('Y-m-d H:i:s')];
    }
}

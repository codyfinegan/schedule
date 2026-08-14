<?php

declare(strict_types=1);

namespace Schedule\Tests\Integration\Http;

use Carbon\CarbonImmutable;
use Nyholm\Psr7\ServerRequest;
use Schedule\Http\Controllers\Authenticated\Api\BlocksController;
use Schedule\Http\Exceptions\HttpException;
use Schedule\Models\GameBlock;
use Schedule\Tests\TestCase;

final class BlockRsvpLockingTest extends TestCase
{
    public function test_users_can_rsvp_and_unrsvp_to_an_upcoming_block(): void
    {
        $user = $this->makeUser();
        $block = $this->makeBlock(CarbonImmutable::now('UTC')->addDay(), CarbonImmutable::now('UTC')->addDay()->addHours(3));

        $request = $this->requestAs($user, 'POST', "/api/blocks/{$block->id}/rsvp");
        $response = $this->call([BlocksController::class, 'rsvp'], $request, ['id' => (string) $block->id]);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($this->jsonOf($response)['data']['viewer_has_rsvped']);
        self::assertSame(1, $block->fresh()->rsvps()->count());

        $request = $this->requestAs($user, 'DELETE', "/api/blocks/{$block->id}/rsvp");
        $response = $this->call([BlocksController::class, 'unrsvp'], $request, ['id' => (string) $block->id]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame(0, $block->fresh()->rsvps()->count());
    }

    public function test_rsvp_is_rejected_once_the_block_has_ended(): void
    {
        $user = $this->makeUser();
        $block = $this->makeBlock(
            CarbonImmutable::now('UTC')->subHours(5),
            CarbonImmutable::now('UTC')->subHours(2),
        );

        $request = $this->requestAs($user, 'POST', "/api/blocks/{$block->id}/rsvp");

        try {
            $this->call([BlocksController::class, 'rsvp'], $request, ['id' => (string) $block->id]);
            self::fail('Expected a 409 HttpException for RSVPing to a past block.');
        } catch (HttpException $e) {
            self::assertSame(409, $e->status);
        }

        self::assertSame(0, $block->rsvps()->count());
    }

    public function test_a_block_is_only_considered_past_once_ends_at_has_passed(): void
    {
        $future = $this->makeBlock(CarbonImmutable::now('UTC')->addMinute(), CarbonImmutable::now('UTC')->addHours(2));
        $justEnded = $this->makeBlock(CarbonImmutable::now('UTC')->subHours(3), CarbonImmutable::now('UTC')->subSecond());

        self::assertFalse($future->isPast());
        self::assertTrue($justEnded->isPast());
    }

    public function test_upcoming_and_past_listing_split_on_the_ends_at_cutoff(): void
    {
        $user = $this->makeUser();
        $past = $this->makeBlock(CarbonImmutable::now('UTC')->subDays(2), CarbonImmutable::now('UTC')->subDays(2)->addHours(3));
        $upcoming = $this->makeBlock(CarbonImmutable::now('UTC')->addDays(2), CarbonImmutable::now('UTC')->addDays(2)->addHours(3));

        $request = $this->requestAs($user, 'GET', '/api/blocks?when=past');
        $request = $request->withQueryParams(['when' => 'past']);
        $response = $this->call([BlocksController::class, 'index'], $request);
        $ids = array_column($this->jsonOf($response)['data'], 'id');

        self::assertContains($past->id, $ids);
        self::assertNotContains($upcoming->id, $ids);

        $request = $this->requestAs($user, 'GET', '/api/blocks?when=upcoming');
        $request = $request->withQueryParams(['when' => 'upcoming']);
        $response = $this->call([BlocksController::class, 'index'], $request);
        $ids = array_column($this->jsonOf($response)['data'], 'id');

        self::assertContains($upcoming->id, $ids);
        self::assertNotContains($past->id, $ids);
    }

    private function makeBlock(CarbonImmutable $startsAt, CarbonImmutable $endsAt): GameBlock
    {
        $creator = $this->makeUser();

        return GameBlock::create([
            'starts_at' => $startsAt->format('Y-m-d H:i:s'),
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
            'created_by_user_id' => $creator->id,
        ]);
    }
}

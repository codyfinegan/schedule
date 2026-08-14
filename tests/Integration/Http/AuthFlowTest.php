<?php

declare(strict_types=1);

namespace Schedule\Tests\Integration\Http;

use Carbon\CarbonImmutable;
use Nyholm\Psr7\ServerRequest;
use Schedule\Http\Controllers\Public\Api\AuthController;
use Schedule\Http\Exceptions\HttpException;
use Schedule\Models\LoginCode;
use Schedule\Models\User;
use Schedule\Tests\TestCase;

final class AuthFlowTest extends TestCase
{
    public function test_request_code_creates_user_and_login_code_but_never_leaks_existence(): void
    {
        $request = (new ServerRequest('POST', '/api/auth/request-code'))
            ->withParsedBody(['email' => 'new-player@example.test']);

        $response = $this->call([AuthController::class, 'requestCode'], $request);

        self::assertSame(200, $response->getStatusCode());

        $user = User::query()->where('email', 'new-player@example.test')->first();
        self::assertNotNull($user);
        self::assertFalse((bool) $user->is_approved);
        self::assertSame(1, LoginCode::query()->where('user_id', $user->id)->count());
    }

    public function test_verify_code_rejects_an_invalid_code(): void
    {
        $user = $this->makeUser(['is_approved' => true]);
        $this->issueCode($user, '111111');

        $request = (new ServerRequest('POST', '/api/auth/verify-code'))
            ->withParsedBody(['email' => $user->email, 'code' => '999999']);

        $this->expectHttpStatus(401, fn () => $this->call([AuthController::class, 'verifyCode'], $request));
    }

    public function test_verify_code_rejects_unapproved_users_with_a_clear_message(): void
    {
        $user = $this->makeUser(['is_approved' => false]);
        $this->issueCode($user, '222222');

        $request = (new ServerRequest('POST', '/api/auth/verify-code'))
            ->withParsedBody(['email' => $user->email, 'code' => '222222']);

        $this->expectHttpStatus(403, fn () => $this->call([AuthController::class, 'verifyCode'], $request));
    }

    public function test_verify_code_logs_in_an_approved_user_and_sets_the_session_cookie(): void
    {
        $user = $this->makeUser(['is_approved' => true]);
        $this->issueCode($user, '333333');

        $request = (new ServerRequest('POST', '/api/auth/verify-code'))
            ->withParsedBody(['email' => $user->email, 'code' => '333333']);

        $response = $this->call([AuthController::class, 'verifyCode'], $request);

        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($response->getHeader('Set-Cookie'));
        self::assertStringContainsString('HttpOnly', $response->getHeaderLine('Set-Cookie'));

        $body = $this->jsonOf($response);
        self::assertSame($user->email, $body['user']['email']);
    }

    public function test_verify_code_cannot_reuse_a_consumed_code(): void
    {
        $user = $this->makeUser(['is_approved' => true]);
        $this->issueCode($user, '444444');

        $request = fn () => (new ServerRequest('POST', '/api/auth/verify-code'))
            ->withParsedBody(['email' => $user->email, 'code' => '444444']);

        $this->call([AuthController::class, 'verifyCode'], $request());

        $this->expectHttpStatus(401, fn () => $this->call([AuthController::class, 'verifyCode'], $request()));
    }

    private function issueCode(User $user, string $plainCode): LoginCode
    {
        return LoginCode::create([
            'user_id' => $user->id,
            'code_hash' => password_hash($plainCode, PASSWORD_DEFAULT),
            'expires_at' => CarbonImmutable::now('UTC')->addMinutes(15),
        ]);
    }

    private function expectHttpStatus(int $status, callable $callback): void
    {
        try {
            $callback();
            self::fail("Expected HttpException with status {$status}, none was thrown.");
        } catch (HttpException $e) {
            self::assertSame($status, $e->status);
        }
    }
}

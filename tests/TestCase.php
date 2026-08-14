<?php

declare(strict_types=1);

namespace Schedule\Tests;

use Carbon\CarbonImmutable;
use DI\Container;
use Illuminate\Database\Capsule\Manager;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Models\Session;
use Schedule\Models\User;

abstract class TestCase extends BaseTestCase
{
    private static ?Container $container = null;

    protected function container(): Container
    {
        if (self::$container === null) {
            self::$container = require dirname(__DIR__) . '/app/bootstrap.php';
        }

        return self::$container;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->container()->get(Manager::class)->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->container()->get(Manager::class)->getConnection()->rollBack();
        parent::tearDown();
    }

    protected function makeUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'email' => 'user' . random_int(1, PHP_INT_MAX) . '@example.test',
            'is_approved' => true,
            'is_admin' => false,
        ], $attributes));
    }

    /**
     * Builds a request carrying the given user as an authenticated session,
     * the same way AuthenticateSession attaches it in production.
     */
    protected function requestAs(User $user, string $method = 'GET', string $uri = '/', ?array $body = null): ServerRequestInterface
    {
        $token = bin2hex(random_bytes(16));
        Session::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => CarbonImmutable::now('UTC')->addYear(),
        ]);

        $request = new ServerRequest($method, $uri);
        if ($body !== null) {
            $request = $request->withParsedBody($body)->withHeader('Content-Type', 'application/json');
        }

        return $request->withAttribute('user', $user);
    }

    protected function call(array $handler, ServerRequestInterface $request, array $params = []): ResponseInterface
    {
        return $this->container()->call($handler, ['request' => $request, ...$params]);
    }

    protected function jsonOf(ResponseInterface $response): array
    {
        $response->getBody()->rewind();

        return json_decode((string) $response->getBody(), true) ?? [];
    }
}

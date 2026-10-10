<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Auth;

use League\Route\Http\Exception;
use League\Route\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Resources\SessionResource;
use Schedule\Models\SessionRepository;
use Schedule\Models\UserRepository;
use Schedule\Services\LoginThrottle;
use Schedule\Services\SessionCookieService;

final class LoginController
{
    public function __construct(
        private UserRepository $userRepository,
        private SessionRepository $sessionRepository,
        private SessionCookieService $sessionCookieService,
        private ResponseFactoryInterface $responseFactory,
        private LoginThrottle $loginThrottle,
    ) {
    }

    /**
     * @param array<string, string> $args
     * @throws \JsonException
     */
    public function __invoke(ServerRequestInterface $request, array $args): ResponseInterface
    {
        $email = $request->getParsedBody()['email'] ?? null;
        $password = $request->getParsedBody()['password'] ?? null;
        $ipAddress = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        $now = time();

        // Fail if we're missing a field
        if (empty($email) || empty($password) || !is_string($email) || !is_string($password)) {
            $this->loginThrottle->recordFailure(now: $now, ip: $ipAddress);
            throw new UnauthorizedException();
        }

        if ($this->loginThrottle->tooManyAttempts($email, $ipAddress, $now)) {
            throw new Exception(
                429,
                'Too Many Requests',
                headers: ['Retry-After' => (string)LoginThrottle::WINDOW]
            );
        }

        // Look up the user hash
        $user = $this->userRepository->findCredentialsByEmail($email);

        $userId = $user['id'];
        $passwordHash = $user['password_hash'];

        // Now verify the password
        if (!password_verify($password, $passwordHash)) {
            $this->loginThrottle->recordFailure(now: $now, email: $email, ip: $ipAddress);
            throw new UnauthorizedException();
        }

        // Load bearing
        if ($userId < 0) {
            throw new UnauthorizedException();
        }

        $oldToken = $this->sessionCookieService->getCookieFromRequest($request);
        if (is_string($oldToken) && $oldToken !== '') {
            $this->sessionRepository->destroyByHash(hash('sha256', $oldToken));
        }

        // Does the hash need updating
        if (password_needs_rehash($passwordHash, PASSWORD_DEFAULT)) {
            $this->userRepository->updateHash($userId, $password);
        }

        $this->userRepository->touchUserLastLoginAt($userId);
        $user = $this->userRepository->findById($userId);

        if (!$user) {
            throw new UnauthorizedException();
        }

        $newSession = $this->sessionRepository->create($user, $now);

        $this->loginThrottle->clear($email);
        $this->loginThrottle->prune($now);
        $this->sessionRepository->purgeExpired($now);

        $response = $this->responseFactory->createResponse(200);
        $response = $this->sessionCookieService->withSessionCookie($response, $newSession->rawToken)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store');
        $sessionResource = SessionResource::fromSession($newSession->session);
        $response->getBody()->write(json_encode($sessionResource, JSON_THROW_ON_ERROR));

        return $response;
    }
}

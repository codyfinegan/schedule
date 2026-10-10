<?php

declare(strict_types=1);

namespace Schedule\Http\Controllers\Auth;

use League\Route\Http\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Schedule\Http\Resources\SessionResource;
use Schedule\Models\SessionRepository;
use Schedule\Models\UserRepository;
use Schedule\Services\SessionCookieService;

final class LoginController
{
    public function __construct(
        private UserRepository $userRepository,
        private SessionRepository $sessionRepository,
        private SessionCookieService $sessionCookieService,
        private ResponseFactoryInterface $responseFactory,
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

        // Fail if we're missing a field
        if (empty($email) || empty($password) || !is_string($email) || !is_string($password)) {
            throw new UnauthorizedException();
        }

        // Look up the user hash
        $user = $this->userRepository->findCredentialsByEmail($email);

        $userId = $user['id'];
        $passwordHash = $user['password_hash'];

        // Now verify the password
        if (!password_verify($password, $passwordHash)) {
            throw new UnauthorizedException();
        }

        // Load bearing
        if ($userId < 0) {
            throw new UnauthorizedException();
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

        $newSession = $this->sessionRepository->create($user, time());

        $response = $this->responseFactory->createResponse(200);
        $response = $this->sessionCookieService->withSessionCookie($response, $newSession->rawToken)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Cache-Control', 'no-store');
        $sessionResource = SessionResource::fromSession($newSession->session);
        $response->getBody()->write(json_encode($sessionResource, JSON_THROW_ON_ERROR));

        return $response;
    }
}

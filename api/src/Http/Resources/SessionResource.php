<?php

declare(strict_types=1);

namespace Schedule\Http\Resources;

use Schedule\Models\Session;

final readonly class SessionResource
{
    public function __construct(
        public string $csrfToken,
        public int $expiresAt,
        public CurrentUserResource $user,
    ) {
    }

    public static function fromSession(Session $session): self
    {
        $user = CurrentUserResource::fromUser($session->user);
        return new self($session->csrfToken, $session->expiresAt, $user);
    }
}

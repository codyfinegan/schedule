<?php

declare(strict_types=1);

namespace Schedule\Models;

final readonly class NewSession
{
    public function __construct(
        public Session $session,
        public string $rawToken,
    ) {
    }
}

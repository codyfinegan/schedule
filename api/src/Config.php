<?php

declare(strict_types=1);

namespace Schedule;

final class Config
{
    private const array REQUIRED = [
        'DATABASE_PATH', 'APP_URL', 'ORIGIN_URL', 'MAILER_DSN', 'MAIL_FROM', 'RSVP_WEBHOOK_SECRET',
    ];

    public function __construct(
        protected(set) string $databasePath,
        protected(set) string $appUrl,
        protected(set) string $originUrl,
        protected(set) string $mailerDsn,
        protected(set) string $mailerFrom,
        protected(set) string $rsvpWebhookSecret,
    ) {
    }

    /**
     * Create a new configuration option based on the env vars.
     *
     * @param null|array<string, string> $env
     * @return self
     */
    public static function fromEnv(?array $env = null): self
    {
        $env ??= getenv();

        $missing = array_filter(
            self::REQUIRED,
            static fn(string $key): bool => !isset($env[$key]) || $env[$key] === '',
        );
        if ($missing !== []) {
            throw new \RuntimeException('Missing required env vars: ' . implode(', ', $missing));
        }

        return new self(
            databasePath: $env['DATABASE_PATH'],
            appUrl: $env['APP_URL'],
            originUrl: $env['ORIGIN_URL'],
            mailerDsn: $env['MAILER_DSN'],
            mailerFrom: $env['MAIL_FROM'],
            rsvpWebhookSecret: $env['RSVP_WEBHOOK_SECRET'],
        );
    }
}

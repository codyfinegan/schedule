<?php

declare(strict_types=1);

namespace Schedule;

final class Config
{
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

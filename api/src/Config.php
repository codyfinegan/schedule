<?php

declare(strict_types=1);

namespace Schedule;

final class Config
{
    public function __construct(
        protected(set) string $database_path,
        protected(set) string $app_url,
        protected(set) string $origin_url,
        protected(set) string $mailer_dsn,
        protected(set) string $mailer_from,
        protected(set) string $rsvp_webhook_secret,
    ) {
    }

    /**
     * Create a new configuration option based on the env vars.
     *
     * @param null|array $env
     * @return self
     */
    public static function fromEnv(?array $env = null): self
    {
        $env ??= getenv();

        return new self(
            database_path: $env['DATABASE_PATH'],
            app_url: $env['APP_URL'],
            origin_url: $env['ORIGIN_URL'],
            mailer_dsn: $env['MAILER_DSN'],
            mailer_from: $env['MAIL_FROM'],
            rsvp_webhook_secret: $env['RSVP_WEBHOOK_SECRET'],
        );
    }
}
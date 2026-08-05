# Library Backend

This is the PHP-based backend that holds library items.

## Local development

The dev stack runs in Docker Compose: FrankenPHP (PHP 8.5) serving `public/` over plain HTTP, backed by SQLite (via `pdo_sqlite`), with the database file at `var/database.sqlite`. HTTPS is terminated upstream by an internal proxy in front of this stack, so Caddy itself only needs to serve HTTP.

1. Install Docker + Docker Compose v2.
2. `cp .env.example .env`
3. `docker compose up -d --build`
4. Visit `http://localhost:8082` (or wherever your proxy routes to it).

## Dependencies

PHP dependencies are managed with [Composer](https://getcomposer.org/), but Composer itself isn't baked into the `php` image — it runs on demand via the official `composer` Docker image, wired up as a separate `composer` service (`profiles: [tools]`) so it never starts as part of `docker compose up`.

```
docker compose run --rm composer install
docker compose run --rm composer require <vendor/package>
docker compose run --rm composer update
```

`vendor/` is gitignored; `composer.json` (and `composer.lock`, once dependencies exist) are committed.

### Teardown

- `docker compose down` stops the stack, keeping data (the SQLite database lives in `var/` on the host, not in a volume, so it's unaffected either way).
- To reset the database, just delete `var/database.sqlite`.

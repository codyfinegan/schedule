# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project state

This is a PHP-based backend for holding library items, in early/skeleton form:

- `public/index.php` — the only PHP file in the project and the current web entry point. It's a stub (echoes `Yep.`, with a `phpinfo()` debug branch behind `$_GET['info']`).
- `src/` — exists but is currently empty; this is where application code is expected to live as the project grows.
- `composer.json` declares this a PHP ≥8.5 project with a PSR-4 autoload mapping (`Schedule\` → `src/`) but no third-party dependencies yet. Composer itself is not installed into the app image — it runs on demand via the official `composer` Docker image (see "Common commands" below); `composer.lock`/`vendor/` won't exist until someone runs `composer install`. No test framework or linter has been set up yet — check for their config files before relying on them, since this section will go stale as the project grows.

Given the minimal state, there are no established architectural patterns to follow yet — when adding the first real code, check with the user on structure/framework preferences rather than assuming conventions from a typical PHP project.

## Development environment

The dev stack is Docker Compose-based: a single `php` service (FrankenPHP, PHP 8.5, built from `docker/php/Dockerfile`) serving `public/` over plain HTTP, backed by SQLite (`pdo_sqlite`/`sqlite3` extensions) with the database file at `var/database.sqlite` on the host (bind-mounted into the container, so it persists without a named volume). HTTPS is terminated by an internal proxy in front of this stack, not by Caddy itself. Full setup/teardown steps are in `readme.md`.

Common commands (run from the repo root, on whichever host runs Docker):
- `docker compose up -d --build` — build and start the stack.
- `docker compose exec php <cmd>` — run a command inside the PHP container (e.g. `sh` for a shell, or `sqlite3 var/database.sqlite` for a DB shell).
- `docker compose logs -f php` — tail FrankenPHP/Caddy logs.
- `docker compose down` — stop the stack (the SQLite file lives in `var/` on the host and is unaffected).
- `docker compose run --rm composer install` — install PHP dependencies (Composer runs via a dedicated `composer` service that never starts with `up`; it only runs when invoked explicitly like this).
- `docker compose run --rm composer require <vendor/package>` — add a dependency.
- `docker compose run --rm composer update` — update dependencies within their version constraints.

Config lives in `docker-compose.yml`, `docker/php/Dockerfile`, `docker/php/Caddyfile`, `docker/php/conf.d/dev.ini`, and `composer.json`; environment values (`DATABASE_PATH`) come from `.env` (copy from `.env.example`, gitignored).

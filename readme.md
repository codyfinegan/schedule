# Schedule App

This is a very simple PHP-based (backend) & JS-base (frontend) application to manage scheduling games of Blood on the Clocktower (or BOTC).

## Features

### User Management
- The site contains users
- Users can sign up via email, but the account must be confirmed and approved by an admin before it can login
- Once signed up and logged in (via a long-lasting session cookie, or a emailed code, (no passwords if possible)) a user can see both upcoming and past games
- OR alternatively we use a SSO service instead for logging in.

### Game Sessions
- The app presents two views, a list of games (split into current/future and then past games via a tab/another view) and a calendar showing the game blocks.
- Each game block represents a date/time blocked out (think Calendar Event) that people can  RSVP themselves to.
- Past game blocks become locked, you can't RSVP to them anymore.
- Each game block contains multiple game sessions. These are entered afterwards and include a story teller, list of players (as some may drop out or join later), and what script was played.
- For current game blocks, the botc.app session code is linked so people can join

### Scheduling
- Administrators can create manual game session blocks, or recurring schedules. If recurring then they'll be automatically created for the next 2 months.
- Past game blocks are readonly
- Users can RSVP to any current or upcoming game. If they RSVP they'll get a reminder (depending on what reminder settings they choose).

### Discord
- We have a discord channel where new games are linked in optionally.

> Note: game sessions (storyteller/script/player-list logging), reminders, and the Discord integration are **v2** and not yet implemented. v1 covers auth, admin approval, manual/recurring game blocks, RSVP, and the list/calendar views.

## Architecture

- **Backend**: PHP 8.5, PSR-7/PSR-15 HTTP layer (`nyholm/psr7` + `relay/relay`), `nikic/fast-route` router, `php-di/php-di` for DI, standalone Eloquent (`illuminate/database`) on SQLite, `robmorgan/phinx` migrations, `symfony/console` for CLI, `symfony/mailer` for email (Mailpit in dev).
- **Frontend**: each page (`/login`, `/blocks`, `/calendar`, `/admin`) is a PHP controller (`src/Http/Controllers/Pages/`) rendering a shared PHP layout (`resources/views/layout.php`) around a page-specific Vue 3 + TypeScript "island" (`client/src/entries/*.ts` mounting a view from `client/src/views/`), using PrimeVue components. Vite builds one bundle per page (`client/vite.config.ts`'s multi-entry config) into `storage/frontend/` (outside the web root) — no Node server at runtime, and no client-side router.
- Runs on FrankenPHP/Caddy, but all routing goes through PHP: Caddy forwards every request to `public/index.php`, which dispatches `/api/*` to the API controllers, page routes (`/login`, `/blocks`, `/calendar`, `/admin`, `/`) to the `Pages\*` controllers, `/assets/*` to `FrontendController` (streaming Vite's built JS/CSS bundles), and anything else to an HTML 404 page. `src/View/ViteManifest.php` resolves a page's entry name to the right hashed asset URLs by reading Vite's `manifest.json`.

## Dev environment setup

Requires Docker Compose. All PHP/Composer/Node commands run inside containers — nothing needs to be installed on the host.

### 1. Configure environment

```
cp .env.example .env   # already present in this repo; adjust as needed
```

Key env vars (see `.env.example`): `DATABASE_PATH`, `APP_TIMEZONE`, `APP_URL`, `SESSION_COOKIE_SECURE`, `MAILER_DSN`, `MAIL_FROM`.

### 2. Bring up the stack

```
docker compose up -d --build
```

This starts the `php` (FrankenPHP/Caddy) service on `127.0.0.1:8082` and a `mailpit` service (SMTP catcher for dev email, web UI on `127.0.0.1:8083`). The reverse proxy at `schedule.blade.lan` is already wired to `127.0.0.1:8082`.

### 3. Install PHP dependencies

```
docker compose run --rm composer install
```

### 4. Run database migrations

```
docker compose exec php php console.php migrate
```

### 5. Bootstrap the first admin

Since admin approval is otherwise circular for the very first user:

```
docker compose exec php php console.php admin:promote you@example.com
```

This creates the user if needed and sets `is_admin`/`is_approved` to true.

### 6. Build the frontend

```
docker compose run --rm frontend
```

Runs `npm install && npm run build` in a `node:20-alpine` container, outputting per-page bundles + `.vite/manifest.json` to `storage/frontend/` (assets served by PHP's `FrontendController`, not directly by Caddy). Re-run this whenever `client/` changes; for iterative frontend work, run `cd client && npm run dev` locally and set `VITE_DEV_SERVER_URL=http://127.0.0.1:5173` in `.env` so page controllers point at the Vite dev server instead of the built manifest.

### 7. Verify

- `https://schedule.blade.lan/` should redirect to `/login` (or `/blocks` once logged in).
- `curl https://schedule.blade.lan/api/me` should return `401` before logging in.
- Log in via the `/login` page: request a code, then open Mailpit (`http://127.0.0.1:8083`) to read the emailed code and complete verification. The session cookie persists for a year across reloads.

## Recurring game blocks

Recurring schedules are eagerly materialized into concrete `game_blocks` rows out to a rolling 2-month horizon (not computed virtually), so RSVPs stay simple foreign-key attachments. Materialization runs automatically when a recurring schedule is created/reactivated via the API, and should also run periodically to keep the horizon full:

```
docker compose exec php php console.php schedule:generate-blocks
```

Wire this into host cron (or any external scheduler) to run e.g. daily:

```
0 3 * * * cd /path/to/schedule && docker compose exec -T php php console.php schedule:generate-blocks
```

## Tests

```
docker compose exec php php vendor/bin/phpunit
```

Tests run against an isolated temp-file SQLite database (migrated fresh per test run) — they never touch `var/database.sqlite`.


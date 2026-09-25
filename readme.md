# Schedule App

This is a very simple PHP-based (backend) & JS-base (frontend) application to manage scheduling games of Blood on the
Clocktower (or BOTC).

## Features

### Scheduled Games

The storyteller can schedule a game and invite people who will be emailed out a regular calendar invite.
The response to the calendar invite is synced back in the app.
Recurring schedules are handled in-app instead of via different calendars.

### RSVP

Players can RSVP yes, no or traveling. They can see how many players are currently signed up for the game
and if the minimum game threshold has been set.

### Calendar

Upcoming games can be indicated on a public page and players can add themselves or invite others too.

## Architecture

- `client/` — the frontend. In prod this is built and pushed to an S3 bucket served through CloudFront; nothing here is PHP-aware.
- `api/` — a PHP-FPM API, no view rendering. In prod it runs on a small droplet behind its own Caddy, backed by SQLite on a persistent volume.
- `mail-worker/` — dev-only. Locally stands in for Cloudflare Email Routing + a Worker, which is what actually parses inbound RSVP replies and forwards them to the API's webhook in prod.

## Development

Docker only — no PHP, Composer, or Node expected on the host.

```
docker compose up -d --build
```

This starts `client`, `api-caddy`, `api-php`, `mailpit`, and `mail-worker`, each publishing a fixed port on `127.0.0.1` (see `docker-compose.override.yml`) — 8085/8086/8087 for client/API/Mailpit respectively. This repo doesn't assume any particular local domain; if you want `https://`-style URLs instead of raw ports, point your own reverse proxy at those ports and set `RSVP_ADDRESS` in `.env` (see `.env.example`) to match whatever address you route mail to.

Once it's up, Mailpit's UI is where outbound mail the API sends shows up in dev, and it's also where you simulate an inbound RSVP reply by sending mail back into it.

### PHP dependencies

```
docker compose run --rm composer install
```

`composer.lock` is committed; `api/vendor/` is not — it's populated by the command above (dev) or baked in at image build time (prod).

### Production

`docker-compose.prod.yml` is a separate override, not auto-loaded — it deliberately excludes everything dev-only (`client`, `mailpit`, `mail-worker`), since those are replaced by S3 + CloudFront and Cloudflare Email Routing + a Worker respectively. It requires `API_DOMAIN` (the hostname Caddy requests its own Let's Encrypt certificate for — see `.env.example`):

```
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

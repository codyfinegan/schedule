# Composer, git, and unzip only exist in this stage — none of them end up
# in the final image below.
FROM php:8.5-fpm-alpine AS vendor

RUN apk add --no-cache git unzip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Installed from the manifest first so this layer only rebuilds when
# dependencies change, not on every source edit.
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

FROM php:8.5-fpm-alpine

RUN apk add --no-cache sqlite-dev \
    && docker-php-ext-install pdo_sqlite

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor

# Baked in so the prod image is self-contained. Dev shadows this with a live
# bind-mount (docker-compose.override.yml), so edits there don't need a rebuild.
COPY . .

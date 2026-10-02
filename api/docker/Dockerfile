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

RUN echo 'expose_php = Off' > "$PHP_INI_DIR/conf.d/zz-hardening.ini"

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor

# Baked in so the prod image is self-contained. Dev shadows this with a live
# bind-mount (docker-compose.override.yml), so edits there don't need a rebuild.
COPY . .

# A fresh named volume copies this directory's ownership, so the SQLite
# volume mounted here starts out writable by www-data.
RUN mkdir -p var && chown www-data:www-data var

# Runs migrations, then hands off to the base image's entrypoint (php-fpm).
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/entrypoint.sh

# Everything from here on, migrations and FPM included, runs unprivileged.
USER www-data

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

#!/bin/sh
set -e

# Bring the schema up to date before PHP-FPM takes any requests. Runs on
# every container start; already-applied migrations are skipped.
php /app/bin/migrate.php

# Hand off to the base image's entrypoint so CMD (php-fpm) runs as normal.
exec docker-php-entrypoint "$@"

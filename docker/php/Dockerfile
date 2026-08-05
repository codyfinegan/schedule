FROM dunglas/frankenphp:1.12.0-php8.5-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends sqlite3 && rm -rf /var/lib/apt/lists/*
RUN install-php-extensions pdo_sqlite sqlite3 opcache

WORKDIR /app

<?php

$env = function ($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }

    return $value;
};

$envBool = function ($key, $default = false) use ($env) {
    $value = $env($key, null);
    if ($value === null) {
        return $default;
    }

    return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
};

return [
    'debug' => $envBool('APP_DEBUG', false),
    'timezone' => $env('APP_TIMEZONE', 'UTC'),
    'url' => $env('APP_URL', 'http://localhost'),

    'db' => [
        'driver' => 'sqlite',
        'database' => $env('DATABASE_PATH', __DIR__ . '/../var/database.sqlite'),
        'prefix' => '',
    ],

    'session' => [
        'cookie_name' => 'schedule_session',
        'ttl_days' => 365,
        'secure' => $envBool('SESSION_COOKIE_SECURE', true),
    ],

    'login_code' => [
        'ttl_minutes' => 15,
    ],

    'mail' => [
        'dsn' => $env('MAILER_DSN', 'smtp://mailpit:1025'),
        'from' => $env('MAIL_FROM', 'schedule@blade.lan'),
    ],

    'recurring' => [
        'horizon_months' => 2,
    ],

    'frontend' => [
        'dist_path' => __DIR__ . '/../storage/frontend',
    ],

    'vite' => [
        // Set (e.g. http://127.0.0.1:5173) to point pages at a running
        // `npm run dev` Vite server instead of the built manifest.
        'dev_server_url' => $env('VITE_DEV_SERVER_URL', null),
    ],
];

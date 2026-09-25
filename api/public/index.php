<?php

// Placeholder entrypoint to verify the Caddy + PHP-FPM + SQLite plumbing.
// Replace once real routing exists.

header('Content-Type: application/json');
echo json_encode([
    'status' => 'ok',
    'service' => 'schedule-api',
    'php' => PHP_VERSION,
]);

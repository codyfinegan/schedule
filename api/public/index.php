<?php

require __DIR__ . '/../vendor/autoload.php';

try {
    new \Schedule\Kernel(__DIR__ . '/..')
        ->boot()
        ->run();
} catch (\Throwable $exception) {
    error_log((string)$exception);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo '{"status_code": 500, "reason_phrase": "Internal Server Error"}';
}

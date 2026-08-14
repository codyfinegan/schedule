<?php

declare(strict_types=1);

if (!function_exists('e')) {
    /**
     * HTML-escapes a value for use in PHP view templates.
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

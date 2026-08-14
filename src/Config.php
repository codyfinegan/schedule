<?php

declare(strict_types=1);

namespace Schedule;

final class Config
{
    private array $storage;

    public function __construct(string $path)
    {
        $this->storage = require $path;
    }

    public function __get(string $key): mixed
    {
        return $this->storage[$key] ?? null;
    }

    /**
     * Dot-notation lookup, e.g. get('db.database').
     */
    public function get(string $dotKey, mixed $default = null): mixed
    {
        $value = $this->storage;
        foreach (explode('.', $dotKey) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

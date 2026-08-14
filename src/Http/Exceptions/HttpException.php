<?php

declare(strict_types=1);

namespace Schedule\Http\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public static function unauthorized(string $message = 'Authentication required.'): self
    {
        return new self(401, $message);
    }

    public static function forbidden(string $message = 'Forbidden.'): self
    {
        return new self(403, $message);
    }

    public static function notFound(string $message = 'Not found.'): self
    {
        return new self(404, $message);
    }

    public static function conflict(string $message = 'Conflict.'): self
    {
        return new self(409, $message);
    }

    public static function unprocessable(string $message, array $errors = []): self
    {
        return new self(422, $message, $errors);
    }
}

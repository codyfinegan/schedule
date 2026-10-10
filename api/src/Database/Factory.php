<?php

declare(strict_types=1);

namespace Schedule\Database;

final class Factory
{
    public static function create(string $path): Database
    {
        $db = new Database('sqlite:' . $path, options: [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);
        $db->exec('PRAGMA foreign_keys = ON;');
        $db->exec('PRAGMA busy_timeout = 5000;');
        $db->exec('PRAGMA synchronous = NORMAL;');

        return $db;
    }
}

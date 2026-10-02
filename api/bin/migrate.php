<?php

declare(strict_types=1);

use Schedule\Database\Database;
use Schedule\Kernel;

require_once __DIR__ . '/../vendor/autoload.php';

$container = new Kernel(__DIR__ . '/..')->container();

$pdo = $container->get(Database::class);
$current = (int) $pdo->query('PRAGMA user_version')->fetchColumn();

foreach (glob(__DIR__ . '/../migrations/*.sql') as $file) {
    $version = (int) basename($file, '.sql');
    if ($version <= $current) {
        continue;
    }

    $pdo->exec('BEGIN IMMEDIATE');
    try {
        $pdo->exec(file_get_contents($file));
        $pdo->exec("PRAGMA user_version = $version");
        $pdo->exec('COMMIT');
    } catch (Throwable $e) {
        $pdo->exec('ROLLBACK');
        throw $e;
    }
}

// All good
exit(0);
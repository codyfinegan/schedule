<?php

require_once __DIR__ . '/vendor/autoload.php';

use Schedule\Config;

$db = (new Config(__DIR__ . '/app/config.php'))->db;

return [
    'paths' => [
        'migrations' => 'db/migrations',
        'seeds' => 'db/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'development',
        'development' => [
            'adapter' => $db['driver'],
            'name' => $db['database'],
            'suffix' => '',
        ],
    ],
];

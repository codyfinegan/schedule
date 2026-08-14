<?php

/**
 * Test bootstrap: builds an isolated temp-file SQLite database and runs the
 * real Phinx migrations against it (via a separate `console.php migrate`
 * process, since Phinx opens its own DB connection independent of
 * Eloquent's) *before* any test touches the app container. DATABASE_PATH is
 * set here so app/config.php's getenv() picks it up for every test.
 *
 * A temp file (not :memory:) is required: Phinx and Eloquent would each
 * open their own independent in-memory database otherwise, so the
 * migrated schema would never be visible to the app's Eloquent connection.
 */

require __DIR__ . '/../vendor/autoload.php';

$dbFile = sys_get_temp_dir() . '/schedule-test-' . bin2hex(random_bytes(6)) . '.sqlite';

putenv('DATABASE_PATH=' . $dbFile);
$_ENV['DATABASE_PATH'] = $dbFile;
$_SERVER['DATABASE_PATH'] = $dbFile;

$root = dirname(__DIR__);
$cmd = sprintf(
    'DATABASE_PATH=%s php %s migrate --quiet -n 2>&1',
    escapeshellarg($dbFile),
    escapeshellarg($root . '/console.php'),
);

exec($cmd, $output, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "Test database migration failed:\n" . implode("\n", $output) . "\n");
    exit(1);
}

register_shutdown_function(static function () use ($dbFile): void {
    @unlink($dbFile);
    @unlink($dbFile . '-journal');
});

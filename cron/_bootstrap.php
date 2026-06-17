<?php

declare(strict_types=1);

/** Shared bootstrap for all cron scripts. */

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';

if (is_file(APP_ROOT . '/.env')) {
    Dotenv\Dotenv::createImmutable(APP_ROOT)->safeLoad();
}

date_default_timezone_set('UTC');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/logs/cron-error.log');

function cron_log(string $msg): void
{
    echo '[' . gmdate('Y-m-d H:i:s') . '] ' . $msg . "\n";
}

<?php

declare(strict_types=1);

/** Daily DB backup with rotation. */

require __DIR__ . '/_bootstrap.php';

use App\Services\BackupService;

try {
    $file = BackupService::create();
    cron_log('backup done: ' . basename($file));
} catch (\Throwable $e) {
    cron_log('backup FAILED: ' . $e->getMessage());
    exit(1);
}

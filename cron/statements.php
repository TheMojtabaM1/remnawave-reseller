<?php

declare(strict_types=1);

/**
 * Generate monthly statements. Run on the 1st of each month; by default it
 * generates the PREVIOUS month. Pass a YYYY-MM argument to target a period.
 *   php cron/statements.php 2026-05
 */

require __DIR__ . '/_bootstrap.php';

use App\Services\StatementService;

$period = $argv[1] ?? null;
if (!$period || !preg_match('/^\d{4}-\d{2}$/', $period)) {
    // Previous month.
    $dt = new DateTime('first day of last month', new DateTimeZone('UTC'));
    $period = $dt->format('Y-m');
}

$count = StatementService::generateAll($period);
cron_log("statements done: period={$period} generated={$count}");

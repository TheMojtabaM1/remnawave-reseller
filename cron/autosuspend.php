<?php

declare(strict_types=1);

/**
 * Auto-suspend resellers whose debt exceeds their limit.
 * (Charging also checks this inline; this cron is the safety net.)
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\Db;
use App\Services\AlertService;
use App\Services\AuditLogger;

$rows = Db::all(
    'SELECT * FROM resellers WHERE status="active" AND allow_debt=1 AND debt_limit IS NOT NULL AND balance < -debt_limit'
);

$count = 0;
foreach ($rows as $r) {
    Db::exec('UPDATE resellers SET status="suspended" WHERE id=:id', [':id' => $r['id']]);
    AlertService::raise(
        'reseller_suspended',
        'نماینده «' . ($r['display_name'] ?: $r['username']) . '» به دلیل عبور از سقف بدهی معلق شد.',
        'critical',
        'reseller:' . $r['id']
    );
    AuditLogger::log('reseller.auto_suspend', 'reseller', (int) $r['id'], ['balance' => (int) $r['balance']], 'owner', 0);
    $count++;
}

cron_log("autosuspend done: suspended={$count}");

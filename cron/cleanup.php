<?php

declare(strict_types=1);

/**
 * Delete configs that have been expired for longer than cleanup_grace_days.
 * Removes the user from Remnawave and marks the local row 'deleted'.
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\Config;
use App\Core\Db;
use App\Services\AuditLogger;
use App\Services\RemnawaveClient;
use App\Services\RemnawaveException;

$grace = max(0, (int) Config::get('cleanup_grace_days', 3));
$rw = new RemnawaveClient();

// Configs whose expiry passed more than $grace days ago and not yet deleted.
$rows = Db::all(
    "SELECT * FROM configs
     WHERE status <> 'deleted' AND expires_at IS NOT NULL
       AND expires_at < (UTC_TIMESTAMP() - INTERVAL {$grace} DAY)"
);

$count = 0;
foreach ($rows as $c) {
    try {
        $rw->deleteUser((string) $c['remnawave_uuid']);
    } catch (RemnawaveException $e) {
        if ($e->statusCode !== 404) {
            cron_log('cleanup skip ' . $c['id'] . ': ' . $e->getMessage());
            continue;
        }
    }
    Db::exec('UPDATE configs SET status="deleted", last_synced_at=UTC_TIMESTAMP() WHERE id=:id', [':id' => $c['id']]);
    AuditLogger::log('config.cleanup', 'config', (int) $c['id'], ['username' => $c['remnawave_username']], 'owner', 0);
    $count++;
}

cron_log("cleanup done: deleted={$count} (grace={$grace}d)");

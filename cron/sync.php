<?php

declare(strict_types=1);

/**
 * Usage / expiry / online sync. Run every few minutes.
 * Pulls each active config's used traffic + expiry from Remnawave, and raises
 * traffic-spike / low-balance alerts.
 */

require __DIR__ . '/_bootstrap.php';

use App\Core\Config;
use App\Core\Db;
use App\Services\AlertService;
use App\Services\ConfigService;
use App\Services\RemnawaveClient;
use App\Services\RemnawaveException;

$rw = new RemnawaveClient();
$svc = new ConfigService($rw);

$configs = Db::all('SELECT * FROM configs WHERE status IN ("active","disabled")');
$ok = 0; $fail = 0;
$spikeGb = (int) Config::get('traffic_spike_gb', 50);

foreach ($configs as $c) {
    $prevBytes = (int) $c['last_used_bytes'];
    try {
        $svc->syncUsage($c);
        $ok++;
    } catch (RemnawaveException $e) {
        $fail++;
        continue;
    }

    // Traffic-spike detection (delta since last sync vs threshold).
    $fresh = Db::one('SELECT last_used_bytes, last_synced_at FROM configs WHERE id=:id', [':id' => $c['id']]);
    if ($fresh) {
        $delta = (int) $fresh['last_used_bytes'] - $prevBytes;
        if ($spikeGb > 0 && $delta > $spikeGb * 1073741824) {
            AlertService::raise(
                'traffic_spike',
                'جهش ترافیک در کانفیگ ' . $c['remnawave_username'] . ': ' . round($delta / 1073741824, 1) . ' گیگ از آخرین همگام‌سازی.',
                'warning',
                'config:' . $c['id']
            );
        }
    }
}

// Low-balance alerts.
$threshold = (int) Config::get('low_balance_threshold', 50000);
$low = Db::all('SELECT id, username, display_name, balance FROM resellers WHERE status="active" AND balance < :t', [':t' => $threshold]);
foreach ($low as $r) {
    AlertService::raise(
        'low_balance',
        'موجودی نماینده «' . ($r['display_name'] ?: $r['username']) . '» کم است: ' . number_format((int) $r['balance']) . ' تومان.',
        'warning',
        'reseller:' . $r['id']
    );
}

cron_log("sync done: ok={$ok} fail={$fail}, low_balance=" . count($low));

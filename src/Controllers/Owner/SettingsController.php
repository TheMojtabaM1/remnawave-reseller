<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditLogger;
use App\Services\RemnawaveClient;
use App\Services\RemnawaveException;

final class SettingsController
{
    private const KEYS = [
        'app_name', 'cleanup_grace_days', 'low_balance_threshold',
        'default_traffic_strategy', 'traffic_spike_gb',
    ];

    public function index(): void
    {
        $settings = [];
        foreach (self::KEYS as $k) {
            $settings[$k] = Config::get($k, '');
        }

        // Test Remnawave connectivity.
        $rwStatus = 'unknown';
        $rwError = null;
        try {
            (new RemnawaveClient())->ping();
            $rwStatus = 'ok';
        } catch (RemnawaveException $e) {
            $rwStatus = 'fail';
            $rwError = $e->getMessage();
        }

        View::render('owner/settings', [
            'title' => 'تنظیمات',
            'settings' => $settings,
            'rwStatus' => $rwStatus,
            'rwError' => $rwError,
            'rwBase' => Config::env('RW_BASE_URL', ''),
        ]);
    }

    public function update(Request $request): void
    {
        foreach (self::KEYS as $k) {
            $v = $request->post($k);
            if ($v !== null) {
                Config::set($k, (string) $v);
            }
        }
        AuditLogger::log('settings.update', 'settings', null);
        flash('success', 'تنظیمات ذخیره شد.');
        Response::redirect('/owner/settings');
    }
}

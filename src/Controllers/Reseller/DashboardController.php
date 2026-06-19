<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;
use App\Services\ConfigService;

final class DashboardController
{
    public function index(): void
    {
        $r = Auth::reseller();
        $id = (int) $r['id'];

        $stats = [
            'balance' => (int) $r['balance'],
            'active' => (int) Db::scalar('SELECT COUNT(*) FROM configs WHERE reseller_id=:id AND status="active"', [':id' => $id]),
            'used_slots' => (int) Db::scalar('SELECT COUNT(*) FROM configs WHERE reseller_id=:id AND status IN ("active","disabled")', [':id' => $id]),
            'total' => (int) Db::scalar('SELECT COUNT(*) FROM configs WHERE reseller_id=:id AND status<>"deleted"', [':id' => $id]),
            'allocated_gb' => (int) Db::scalar('SELECT COALESCE(SUM(volume_gb),0) FROM configs WHERE reseller_id=:id AND status IN ("active","disabled")', [':id' => $id]),
            'today' => (int) Db::scalar('SELECT COUNT(*) FROM configs WHERE reseller_id=:id AND DATE(created_at)=UTC_DATE()', [':id' => $id]),
            'sales' => (int) Db::scalar("SELECT COALESCE(SUM(-amount),0) FROM transactions WHERE reseller_id=:id AND type='charge'", [':id' => $id]),
            'max_users' => (int) $r['max_users'],
            'pool_gb' => (int) $r['max_total_traffic_gb'],
        ];

        $expiring = Db::all(
            'SELECT * FROM configs WHERE reseller_id=:id AND status="active" AND expires_at IS NOT NULL
             AND expires_at BETWEEN UTC_TIMESTAMP() AND (UTC_TIMESTAMP() + INTERVAL 5 DAY)
             ORDER BY expires_at LIMIT 10',
            [':id' => $id]
        );
        $recent = Db::all('SELECT * FROM configs WHERE reseller_id=:id AND status<>"deleted" ORDER BY created_at DESC LIMIT 8', [':id' => $id]);

        View::render('reseller/dashboard', [
            'title' => 'داشبورد',
            'r' => $r,
            'stats' => $stats,
            'expiring' => $expiring,
            'recent' => $recent,
            'canCreate' => ConfigService::perm($r, 'can_create_config'),
        ], 'reseller');
    }
}

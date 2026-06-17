<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Db;
use App\Core\View;
use App\Services\ReportService;

final class DashboardController
{
    public function index(): void
    {
        $kpis = ReportService::kpis();
        $series = ReportService::revenueSeries(30);
        $topResellers = ReportService::leaderboard('month');
        $alerts = Db::all('SELECT * FROM alerts WHERE is_read = 0 ORDER BY created_at DESC LIMIT 6');
        $recent = Db::all(
            'SELECT a.*, COALESCE(r.username, ad.username) AS actor_name
             FROM audit_logs a
             LEFT JOIN resellers r ON a.actor_type="reseller" AND r.id=a.actor_id
             LEFT JOIN admins ad ON a.actor_type="owner" AND ad.id=a.actor_id
             ORDER BY a.created_at DESC LIMIT 10'
        );

        View::render('owner/dashboard', [
            'title' => 'داشبورد',
            'kpis' => $kpis,
            'series' => $series,
            'topResellers' => array_slice($topResellers, 0, 5),
            'alerts' => $alerts,
            'recent' => $recent,
        ]);
    }
}

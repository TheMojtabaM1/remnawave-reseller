<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;

/**
 * A reseller's own analytics: sales, traffic, top configs, plan breakdown.
 */
final class ReportController
{
    public function index(): void
    {
        $r = Auth::reseller();
        $id = (int) $r['id'];

        $kpis = [
            'balance'      => (int) $r['balance'],
            'total_sales'  => (int) Db::scalar("SELECT COALESCE(SUM(-amount),0) FROM transactions WHERE reseller_id=:id AND type='charge'", [':id' => $id]),
            'total_refund' => (int) Db::scalar("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE reseller_id=:id AND type='refund'", [':id' => $id]),
            'active'       => (int) Db::scalar("SELECT COUNT(*) FROM configs WHERE reseller_id=:id AND status='active'", [':id' => $id]),
            'total'        => (int) Db::scalar("SELECT COUNT(*) FROM configs WHERE reseller_id=:id AND status<>'deleted'", [':id' => $id]),
            'sold_gb'      => (int) Db::scalar("SELECT COALESCE(SUM(volume_gb),0) FROM configs WHERE reseller_id=:id AND status<>'deleted'", [':id' => $id]),
            'used_gb'      => (int) round((float) Db::scalar("SELECT COALESCE(SUM(last_used_bytes),0) FROM configs WHERE reseller_id=:id", [':id' => $id]) / 1073741824),
        ];

        // 30-day sales series (this reseller only).
        $rows = Db::all(
            "SELECT DATE(created_at) d, COALESCE(SUM(-amount),0) v
             FROM transactions WHERE reseller_id=:id AND type='charge'
               AND created_at >= (UTC_TIMESTAMP() - INTERVAL 30 DAY)
             GROUP BY DATE(created_at)",
            [':id' => $id]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[$row['d']] = (int) $row['v'];
        }
        $labels = $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $day = gmdate('Y-m-d', time() - $i * 86400);
            $labels[] = shamsi($day . ' 00:00:00', 'date');
            $data[] = $map[$day] ?? 0;
        }

        $topConfigs = Db::all(
            "SELECT remnawave_username, volume_gb, last_used_bytes, price_charged, status
             FROM configs WHERE reseller_id=:id AND status<>'deleted'
             ORDER BY last_used_bytes DESC LIMIT 10",
            [':id' => $id]
        );

        $byPlan = Db::all(
            "SELECT COALESCE(p.name,'سفارشی') name, COUNT(c.id) cnt, COALESCE(SUM(c.price_charged),0) revenue
             FROM configs c LEFT JOIN plans p ON p.id=c.plan_id
             WHERE c.reseller_id=:id AND c.status<>'deleted'
             GROUP BY p.id ORDER BY cnt DESC",
            [':id' => $id]
        );

        View::render('reseller/reports', [
            'title' => 'گزارش‌ها',
            'kpis' => $kpis,
            'series' => ['labels' => $labels, 'data' => $data],
            'topConfigs' => $topConfigs,
            'byPlan' => $byPlan,
        ], 'reseller');
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

/**
 * Financial / operational reporting queries used by the owner dashboard,
 * reports page, charts and Excel export.
 */
final class ReportService
{
    /** Top-line KPIs. */
    public static function kpis(): array
    {
        return [
            'total_revenue'   => (int) Db::scalar("SELECT COALESCE(SUM(-amount),0) FROM transactions WHERE type='charge'"),
            'total_refunds'   => (int) Db::scalar("SELECT COALESCE(SUM(amount),0) FROM transactions WHERE type='refund'"),
            'total_configs'   => (int) Db::scalar("SELECT COUNT(*) FROM configs WHERE status <> 'deleted'"),
            'active_configs'  => (int) Db::scalar("SELECT COUNT(*) FROM configs WHERE status='active'"),
            'active_resellers'=> (int) Db::scalar("SELECT COUNT(*) FROM resellers WHERE status='active'"),
            'traffic_sold_gb' => (int) Db::scalar("SELECT COALESCE(SUM(volume_gb),0) FROM configs WHERE status<>'deleted'"),
            'traffic_used_gb' => (int) round((float) Db::scalar("SELECT COALESCE(SUM(last_used_bytes),0) FROM configs") / 1073741824),
        ];
    }

    /** Net revenue (charges − refunds) over a date range, optional reseller. */
    public static function netRevenue(?string $from = null, ?string $to = null, ?int $resellerId = null): int
    {
        $where = ["type IN ('charge','refund')"];
        $params = [];
        if ($from) { $where[] = 'created_at >= :from'; $params[':from'] = $from; }
        if ($to)   { $where[] = 'created_at <= :to';   $params[':to'] = $to; }
        if ($resellerId) { $where[] = 'reseller_id = :rid'; $params[':rid'] = $resellerId; }
        $sql = 'SELECT COALESCE(SUM(-amount),0) FROM transactions WHERE ' . implode(' AND ', $where);
        return (int) Db::scalar($sql, $params);
    }

    /** Per-reseller sales breakdown. */
    public static function perReseller(): array
    {
        return Db::all(
            "SELECT r.id, r.username, r.display_name, r.balance, r.status,
                    COALESCE(SUM(CASE WHEN t.type='charge' THEN -t.amount ELSE 0 END),0) AS total_sales,
                    COALESCE(SUM(CASE WHEN t.type='refund' THEN t.amount ELSE 0 END),0) AS total_refunds,
                    (SELECT COUNT(*) FROM configs c WHERE c.reseller_id=r.id AND c.status<>'deleted') AS configs_count
             FROM resellers r
             LEFT JOIN transactions t ON t.reseller_id = r.id
             GROUP BY r.id
             ORDER BY total_sales DESC"
        );
    }

    /** Daily revenue time series for the last N days (charts). */
    public static function revenueSeries(int $days = 30): array
    {
        $days = max(1, min(365, $days));
        $rows = Db::all(
            "SELECT DATE(created_at) AS d, COALESCE(SUM(-amount),0) AS revenue
             FROM transactions
             WHERE type='charge' AND created_at >= (UTC_TIMESTAMP() - INTERVAL {$days} DAY)
             GROUP BY DATE(created_at) ORDER BY d"
        );
        $map = [];
        foreach ($rows as $r) {
            $map[$r['d']] = (int) $r['revenue'];
        }
        $labels = $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = gmdate('Y-m-d', time() - $i * 86400);
            $labels[] = shamsi($day . ' 00:00:00', 'date');
            $data[] = $map[$day] ?? 0;
        }
        return ['labels' => $labels, 'data' => $data];
    }

    /** Leaderboard: top resellers by sales in a period. */
    public static function leaderboard(string $period = 'month'): array
    {
        $interval = match ($period) {
            'today' => '1 DAY',
            'week'  => '7 DAY',
            'month' => '30 DAY',
            default => null, // all-time
        };
        $where = "t.type='charge'";
        $params = [];
        if ($interval) {
            $where .= " AND t.created_at >= (UTC_TIMESTAMP() - INTERVAL {$interval})";
        }
        return Db::all(
            "SELECT r.id, r.username, r.display_name,
                    COALESCE(SUM(-t.amount),0) AS sales,
                    COUNT(t.id) AS tx_count
             FROM resellers r
             LEFT JOIN transactions t ON t.reseller_id = r.id AND {$where}
             GROUP BY r.id ORDER BY sales DESC LIMIT 50",
            $params
        );
    }

    /** Top plans by number of configs sold. */
    public static function topPlans(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        return Db::all(
            "SELECT p.name, COUNT(c.id) AS sold, COALESCE(SUM(c.price_charged),0) AS revenue
             FROM configs c JOIN plans p ON p.id = c.plan_id
             WHERE c.status <> 'deleted'
             GROUP BY p.id ORDER BY sold DESC LIMIT {$limit}"
        );
    }
}

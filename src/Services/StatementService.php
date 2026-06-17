<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;
use Mpdf\Mpdf;

/**
 * Generates per-reseller monthly statement PDFs and records them.
 */
final class StatementService
{
    /** Generate (or regenerate) statements for all resellers for a given period (YYYY-MM). */
    public static function generateAll(string $period): int
    {
        $resellers = Db::all('SELECT * FROM resellers');
        $count = 0;
        foreach ($resellers as $r) {
            try {
                self::generateFor((int) $r['id'], $period);
                $count++;
            } catch (\Throwable $e) {
                error_log('[statement] reseller ' . $r['id'] . ': ' . $e->getMessage());
            }
        }
        return $count;
    }

    public static function generateFor(int $resellerId, string $period): int
    {
        $reseller = Db::one('SELECT * FROM resellers WHERE id = :id', [':id' => $resellerId]);
        if (!$reseller) {
            throw new \RuntimeException('reseller not found');
        }

        [$start, $end] = self::periodBounds($period);

        $opening = (int) Db::scalar(
            'SELECT balance_after FROM transactions WHERE reseller_id=:id AND created_at < :s ORDER BY id DESC LIMIT 1',
            [':id' => $resellerId, ':s' => $start]
        );
        $closing = Db::scalar(
            'SELECT balance_after FROM transactions WHERE reseller_id=:id AND created_at <= :e ORDER BY id DESC LIMIT 1',
            [':id' => $resellerId, ':e' => $end]
        );
        $closing = $closing === false ? (int) $reseller['balance'] : (int) $closing;

        $totalSales = (int) Db::scalar(
            "SELECT COALESCE(SUM(-amount),0) FROM transactions WHERE reseller_id=:id AND type='charge' AND created_at BETWEEN :s AND :e",
            [':id' => $resellerId, ':s' => $start, ':e' => $end]
        );
        $totalRefunds = (int) Db::scalar(
            "SELECT COALESCE(SUM(amount),0) FROM transactions WHERE reseller_id=:id AND type='refund' AND created_at BETWEEN :s AND :e",
            [':id' => $resellerId, ':s' => $start, ':e' => $end]
        );
        $configsCount = (int) Db::scalar(
            'SELECT COUNT(*) FROM configs WHERE reseller_id=:id AND created_at BETWEEN :s AND :e',
            [':id' => $resellerId, ':s' => $start, ':e' => $end]
        );

        $txs = Db::all(
            'SELECT * FROM transactions WHERE reseller_id=:id AND created_at BETWEEN :s AND :e ORDER BY id',
            [':id' => $resellerId, ':s' => $start, ':e' => $end]
        );

        $pdfPath = self::renderPdf($reseller, $period, compact('opening', 'closing', 'totalSales', 'totalRefunds', 'configsCount', 'txs'));

        $id = (int) Db::scalar('SELECT id FROM monthly_statements WHERE reseller_id=:id AND period=:p', [':id' => $resellerId, ':p' => $period]);
        if ($id) {
            Db::exec(
                'UPDATE monthly_statements SET opening_balance=:o, closing_balance=:c, total_sales=:ts, total_refunds=:tr, configs_count=:cc, pdf_path=:pdf WHERE id=:id',
                [':o' => $opening, ':c' => $closing, ':ts' => $totalSales, ':tr' => $totalRefunds, ':cc' => $configsCount, ':pdf' => $pdfPath, ':id' => $id]
            );
        } else {
            $id = Db::insert(
                'INSERT INTO monthly_statements (reseller_id, period, opening_balance, closing_balance, total_sales, total_refunds, configs_count, pdf_path, created_at)
                 VALUES (:rid,:p,:o,:c,:ts,:tr,:cc,:pdf,UTC_TIMESTAMP())',
                [':rid' => $resellerId, ':p' => $period, ':o' => $opening, ':c' => $closing, ':ts' => $totalSales, ':tr' => $totalRefunds, ':cc' => $configsCount, ':pdf' => $pdfPath]
            );
        }
        return $id;
    }

    private static function periodBounds(string $period): array
    {
        [$y, $m] = array_map('intval', explode('-', $period));
        $start = sprintf('%04d-%02d-01 00:00:00', $y, $m);
        $endDt = (new \DateTime($start, new \DateTimeZone('UTC')))->modify('last day of this month')->setTime(23, 59, 59);
        return [$start, $endDt->format('Y-m-d H:i:s')];
    }

    private static function renderPdf(array $reseller, string $period, array $d): string
    {
        $name = e($reseller['display_name'] ?: $reseller['username']);
        $rows = '';
        $typeLabels = ['topup' => 'شارژ', 'charge' => 'فروش', 'refund' => 'بازگشت وجه', 'manual_adjust' => 'اصلاح دستی', 'gift' => 'هدیه'];
        foreach ($d['txs'] as $t) {
            $rows .= '<tr><td>' . jdate($t['created_at']) . '</td><td>' . ($typeLabels[$t['type']] ?? e($t['type']))
                . '</td><td>' . toman((int) $t['amount']) . '</td><td>' . toman((int) $t['balance_after'])
                . '</td><td>' . e($t['description']) . '</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="5" style="text-align:center">تراکنشی در این دوره ثبت نشده است.</td></tr>';
        }

        $html = '<html dir="rtl"><head><style>
            body{font-family:dejavusans;font-size:11px}
            h2{text-align:center}
            table{width:100%;border-collapse:collapse;margin-top:8px}
            th,td{border:1px solid #999;padding:5px;text-align:right}
            th{background:#eee}
            .sum td{font-weight:bold}
        </style></head><body>
            <h2>صورتحساب ماهانه نماینده</h2>
            <p>نماینده: <b>' . $name . '</b> &nbsp; | &nbsp; دوره: <b>' . e($period) . '</b></p>
            <table class="sum">
                <tr><td>مانده ابتدای دوره</td><td>' . toman($d['opening']) . '</td>
                    <td>مانده پایان دوره</td><td>' . toman($d['closing']) . '</td></tr>
                <tr><td>جمع فروش</td><td>' . toman($d['totalSales']) . '</td>
                    <td>جمع بازگشت وجه</td><td>' . toman($d['totalRefunds']) . '</td></tr>
                <tr><td>تعداد کانفیگ‌های ایجادشده</td><td colspan="3">' . (int) $d['configsCount'] . '</td></tr>
            </table>
            <h3>ریز تراکنش‌ها</h3>
            <table><thead><tr><th>تاریخ</th><th>نوع</th><th>مبلغ</th><th>مانده</th><th>شرح</th></tr></thead>
            <tbody>' . $rows . '</tbody></table>
        </body></html>';

        $dir = dirname(__DIR__, 2) . '/storage/statements';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/statement_' . $reseller['id'] . '_' . $period . '.pdf';

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'directionality' => 'rtl',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => sys_get_temp_dir(),
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($file, \Mpdf\Output\Destination::FILE);

        return $file;
    }
}

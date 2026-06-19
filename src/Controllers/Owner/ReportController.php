<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Response;
use App\Core\View;
use App\Services\ReportService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

final class ReportController
{
    public function index(): void
    {
        View::render('owner/reports', [
            'title' => 'گزارش‌های مالی',
            'kpis' => ReportService::kpis(),
            'perReseller' => ReportService::perReseller(),
            'topPlans' => ReportService::topPlans(10),
            'series' => ReportService::revenueSeries(30),
        ]);
    }

    /** JSON time series for charts (AJAX). */
    public function series(): void
    {
        $days = (int) ($_GET['days'] ?? 30);
        Response::json(ReportService::revenueSeries($days));
    }

    public function exportExcel(): void
    {
        $rows = ReportService::perReseller();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(true);
        $sheet->setTitle('فروش نمایندگان');

        $headers = ['نماینده', 'نام کاربری', 'موجودی', 'کل فروش', 'بازگشت وجه', 'فروش خالص', 'تعداد کانفیگ', 'وضعیت'];
        $sheet->fromArray($headers, null, 'A1');

        $r = 2;
        foreach ($rows as $row) {
            $net = (int) $row['total_sales'] - (int) $row['total_refunds'];
            $sheet->fromArray([
                $row['display_name'] ?: $row['username'],
                $row['username'],
                (int) $row['balance'],
                (int) $row['total_sales'],
                (int) $row['total_refunds'],
                $net,
                (int) $row['configs_count'],
                $row['status'] === 'active' ? 'فعال' : 'معلق',
            ], null, 'A' . $r);
            $r++;
        }
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'remnawave_reseller_resellers_' . gmdate('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        (new Xlsx($spreadsheet))->save('php://output');
        exit;
    }
}

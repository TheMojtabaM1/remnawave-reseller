<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditLogger;
use App\Services\StatementService;

final class StatementController
{
    public function index(): void
    {
        $statements = Db::all(
            'SELECT s.*, r.username, r.display_name FROM monthly_statements s
             JOIN resellers r ON r.id = s.reseller_id
             ORDER BY s.period DESC, r.username LIMIT 300'
        );
        View::render('owner/statements', [
            'title' => 'صورتحساب‌های ماهانه',
            'statements' => $statements,
            'defaultPeriod' => gmdate('Y-m'),
        ]);
    }

    public function generate(Request $request): void
    {
        $period = (string) $request->post('period', gmdate('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            flash('error', 'دوره نامعتبر است (قالب YYYY-MM).');
            Response::redirect('/owner/statements');
        }
        $count = StatementService::generateAll($period);
        AuditLogger::log('statement.generate', 'period', $period, ['count' => $count]);
        flash('success', "صورتحساب برای {$count} نماینده تولید شد.");
        Response::redirect('/owner/statements');
    }

    public function download(Request $request, array $args): void
    {
        $s = Db::one('SELECT * FROM monthly_statements WHERE id = :id', [':id' => (int) $args['id']]);
        if (!$s || !$s['pdf_path'] || !is_file($s['pdf_path'])) {
            Response::abort(404, 'فایل صورتحساب یافت نشد');
        }
        Response::download($s['pdf_path'], 'statement_' . $s['reseller_id'] . '_' . $s['period'] . '.pdf', 'application/pdf');
    }
}

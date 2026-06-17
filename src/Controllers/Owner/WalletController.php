<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Services\WalletService;

final class WalletController
{
    public function adjust(Request $request, array $args): void
    {
        $id = (int) $args['id'];
        $r = Db::one('SELECT * FROM resellers WHERE id=:id', [':id' => $id]);
        if (!$r) {
            Response::abort(404, 'نماینده یافت نشد');
        }

        $op = (string) $request->post('operation', 'add');
        $amount = abs((int) $request->post('amount', 0));
        $reason = trim((string) $request->post('reason')) ?: 'تنظیم دستی توسط مدیر';
        $adminId = Auth::id();

        try {
            switch ($op) {
                case 'add':
                    WalletService::credit($id, $amount, 'topup', $reason, null, $adminId);
                    break;
                case 'subtract':
                    WalletService::credit($id, -$amount, 'manual_adjust', $reason, null, $adminId);
                    break;
                case 'gift':
                    WalletService::credit($id, $amount, 'gift', $reason, null, $adminId);
                    break;
                case 'set':
                    WalletService::setBalance($id, (int) $request->post('amount', 0), $reason, $adminId);
                    break;
                case 'zero':
                    WalletService::setBalance($id, 0, $reason . ' (صفر کردن)', $adminId);
                    break;
                default:
                    flash('error', 'عملیات نامعتبر.');
                    redirect_back('/owner/resellers/' . $id);
            }
            flash('success', 'کیف پول به‌روزرسانی شد.');
        } catch (\Throwable $e) {
            flash('error', 'خطا: ' . $e->getMessage());
        }
        Response::redirect('/owner/resellers/' . $id);
    }
}

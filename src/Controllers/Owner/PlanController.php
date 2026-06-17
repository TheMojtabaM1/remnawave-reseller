<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditLogger;
use App\Services\RemnawaveClient;
use App\Services\RemnawaveException;

final class PlanController
{
    public function index(): void
    {
        $plans = Db::all('SELECT * FROM plans ORDER BY created_at DESC');
        View::render('owner/plans', ['title' => 'پلن‌ها', 'plans' => $plans, 'squads' => $this->squads()]);
    }

    public function store(Request $request): void
    {
        $f = $this->fields($request);
        if ($f === null) {
            Response::redirect('/owner/plans');
        }
        Db::insert(
            'INSERT INTO plans (name, volume_gb, duration_days, price, allowed_squads, hwid_limit, traffic_strategy, status, is_trial, created_at)
             VALUES (:n,:v,:d,:p,:s,:h,:ts,:st,:trial,UTC_TIMESTAMP())',
            $f
        );
        AuditLogger::log('plan.create', 'plan', null, ['name' => $f[':n']]);
        flash('success', 'پلن ایجاد شد.');
        Response::redirect('/owner/plans');
    }

    public function update(Request $request, array $args): void
    {
        $id = (int) $args['id'];
        $f = $this->fields($request);
        if ($f === null) {
            Response::redirect('/owner/plans');
        }
        $f[':id'] = $id;
        Db::exec(
            'UPDATE plans SET name=:n, volume_gb=:v, duration_days=:d, price=:p, allowed_squads=:s,
                    hwid_limit=:h, traffic_strategy=:ts, status=:st, is_trial=:trial WHERE id=:id',
            $f
        );
        AuditLogger::log('plan.update', 'plan', $id);
        flash('success', 'پلن به‌روزرسانی شد.');
        Response::redirect('/owner/plans');
    }

    public function destroy(Request $request, array $args): void
    {
        $id = (int) $args['id'];
        Db::exec('DELETE FROM plans WHERE id=:id', [':id' => $id]);
        AuditLogger::log('plan.delete', 'plan', $id);
        flash('success', 'پلن حذف شد.');
        Response::redirect('/owner/plans');
    }

    private function fields(Request $request): ?array
    {
        $name = trim((string) $request->post('name'));
        $vol = (int) $request->post('volume_gb');
        $days = (int) $request->post('duration_days');
        $price = (int) $request->post('price');
        if ($name === '' || $vol <= 0 || $days <= 0) {
            flash('error', 'نام، حجم و مدت معتبر الزامی است.');
            return null;
        }
        $squads = array_values(array_filter((array) $request->arr('allowed_squads')));
        $strategy = in_array($request->post('traffic_strategy'), ['NO_RESET', 'DAY', 'WEEK', 'MONTH'], true)
            ? $request->post('traffic_strategy') : 'NO_RESET';
        return [
            ':n' => $name, ':v' => $vol, ':d' => $days, ':p' => $price,
            ':s' => json_encode($squads, JSON_UNESCAPED_UNICODE),
            ':h' => (int) $request->post('hwid_limit', 0),
            ':ts' => $strategy,
            ':st' => $request->post('status') === 'inactive' ? 'inactive' : 'active',
            ':trial' => $request->bool('is_trial') ? 1 : 0,
        ];
    }

    private function squads(): array
    {
        try {
            return (new RemnawaveClient())->listInternalSquads();
        } catch (RemnawaveException) {
            return [];
        }
    }
}

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

final class TemplateController
{
    public function index(): void
    {
        $templates = Db::all('SELECT * FROM config_templates ORDER BY created_at DESC');
        $squads = [];
        try {
            $squads = (new RemnawaveClient())->listInternalSquads();
        } catch (RemnawaveException) {
        }
        View::render('owner/templates', ['title' => 'قالب‌ها', 'templates' => $templates, 'squads' => $squads]);
    }

    public function store(Request $request): void
    {
        $f = $this->fields($request);
        if ($f === null) {
            Response::redirect('/owner/templates');
        }
        Db::insert(
            'INSERT INTO config_templates (name, volume_gb, duration_days, squads, hwid_limit, traffic_strategy, naming_pattern, created_at)
             VALUES (:n,:v,:d,:s,:h,:ts,:np,UTC_TIMESTAMP())',
            $f
        );
        AuditLogger::log('template.create', 'template', null, ['name' => $f[':n']]);
        flash('success', 'قالب ایجاد شد.');
        Response::redirect('/owner/templates');
    }

    public function update(Request $request, array $args): void
    {
        $id = (int) $args['id'];
        $f = $this->fields($request);
        if ($f === null) {
            Response::redirect('/owner/templates');
        }
        $f[':id'] = $id;
        Db::exec(
            'UPDATE config_templates SET name=:n, volume_gb=:v, duration_days=:d, squads=:s, hwid_limit=:h, traffic_strategy=:ts, naming_pattern=:np WHERE id=:id',
            $f
        );
        AuditLogger::log('template.update', 'template', $id);
        flash('success', 'قالب به‌روزرسانی شد.');
        Response::redirect('/owner/templates');
    }

    public function destroy(Request $request, array $args): void
    {
        $id = (int) $args['id'];
        Db::exec('DELETE FROM config_templates WHERE id=:id', [':id' => $id]);
        AuditLogger::log('template.delete', 'template', $id);
        flash('success', 'قالب حذف شد.');
        Response::redirect('/owner/templates');
    }

    private function fields(Request $request): ?array
    {
        $name = trim((string) $request->post('name'));
        $vol = (int) $request->post('volume_gb');
        $days = (int) $request->post('duration_days');
        if ($name === '' || $vol <= 0 || $days <= 0) {
            flash('error', 'نام، حجم و مدت معتبر الزامی است.');
            return null;
        }
        $squads = array_values(array_filter((array) $request->arr('squads')));
        $strategy = in_array($request->post('traffic_strategy'), ['NO_RESET', 'DAY', 'WEEK', 'MONTH'], true)
            ? $request->post('traffic_strategy') : 'NO_RESET';
        return [
            ':n' => $name, ':v' => $vol, ':d' => $days,
            ':s' => json_encode($squads, JSON_UNESCAPED_UNICODE),
            ':h' => (int) $request->post('hwid_limit', 0),
            ':ts' => $strategy,
            ':np' => trim((string) $request->post('naming_pattern')),
        ];
    }
}

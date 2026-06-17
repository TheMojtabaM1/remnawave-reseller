<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Db;
use App\Core\Request;
use App\Core\View;

final class AuditController
{
    public function index(Request $request): void
    {
        $actor = trim((string) $request->get('actor', ''));
        $action = trim((string) $request->get('action', ''));
        $page = max(1, $request->int('page', 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $where = ['1=1'];
        $params = [];
        if ($actor !== '') {
            $where[] = 'actor_type = :actor';
            $params[':actor'] = $actor;
        }
        if ($action !== '') {
            $where[] = 'action LIKE :action';
            $params[':action'] = '%' . $action . '%';
        }
        $w = implode(' AND ', $where);

        $total = (int) Db::scalar("SELECT COUNT(*) FROM audit_logs WHERE {$w}", $params);
        $logs = Db::all(
            "SELECT * FROM audit_logs WHERE {$w} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        View::render('owner/audit', [
            'title' => 'لاگ فعالیت‌ها',
            'logs' => $logs,
            'page' => $page,
            'pages' => (int) ceil($total / $perPage),
            'actor' => $actor,
            'action' => $action,
        ]);
    }
}

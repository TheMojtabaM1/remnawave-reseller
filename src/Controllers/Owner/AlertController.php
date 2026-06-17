<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

final class AlertController
{
    public function index(): void
    {
        $alerts = Db::all('SELECT * FROM alerts ORDER BY is_read ASC, created_at DESC LIMIT 200');
        View::render('owner/alerts', ['title' => 'هشدارها', 'alerts' => $alerts]);
    }

    public function markRead(Request $request, array $args): void
    {
        Db::exec('UPDATE alerts SET is_read = 1 WHERE id = :id', [':id' => (int) $args['id']]);
        redirect_back('/owner/alerts');
    }

    public function markAllRead(): void
    {
        Db::exec('UPDATE alerts SET is_read = 1 WHERE is_read = 0');
        flash('success', 'همه هشدارها خوانده‌شده علامت‌گذاری شدند.');
        Response::redirect('/owner/alerts');
    }
}

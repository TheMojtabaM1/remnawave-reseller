<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Request;
use App\Core\View;
use App\Services\ReportService;

final class LeaderboardController
{
    public function index(Request $request): void
    {
        $period = in_array($request->get('period'), ['today', 'week', 'month', 'all'], true)
            ? (string) $request->get('period') : 'month';
        $rows = ReportService::leaderboard($period);
        View::render('owner/leaderboard', ['title' => 'برترین نمایندگان', 'rows' => $rows, 'period' => $period]);
    }
}

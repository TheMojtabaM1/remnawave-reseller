<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Request;
use App\Core\View;

final class WalletController
{
    public function index(Request $request): void
    {
        $r = Auth::reseller();
        $page = max(1, $request->int('page', 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;

        $total = (int) Db::scalar('SELECT COUNT(*) FROM transactions WHERE reseller_id=:id', [':id' => $r['id']]);
        $txs = Db::all(
            "SELECT * FROM transactions WHERE reseller_id=:id ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}",
            [':id' => $r['id']]
        );

        View::render('reseller/wallet', [
            'title' => 'کیف پول',
            'r' => $r,
            'txs' => $txs,
            'page' => $page,
            'pages' => (int) ceil($total / $perPage),
        ], 'reseller');
    }
}

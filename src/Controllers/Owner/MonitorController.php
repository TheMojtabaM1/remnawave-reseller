<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Db;
use App\Core\View;
use App\Services\RemnawaveClient;
use App\Services\RemnawaveException;

final class MonitorController
{
    public function index(): void
    {
        $rw = new RemnawaveClient();
        $error = null;
        $onlineTotal = -1;
        $perReseller = [];

        try {
            $onlineTotal = $rw->onlineCount();
            $users = $rw->listUsers(['size' => 1000]);
            foreach ($users as $u) {
                if (!$this->isOnline($u)) {
                    continue;
                }
                $tag = (string) ($u['tag'] ?? '');
                if (preg_match('/^RSL_(\d+)$/', $tag, $m)) {
                    $rid = (int) $m[1];
                    $perReseller[$rid] = ($perReseller[$rid] ?? 0) + 1;
                }
            }
        } catch (RemnawaveException $e) {
            $error = $e->getMessage();
        }

        $resellers = Db::all('SELECT id, username, display_name FROM resellers ORDER BY username');
        arsort($perReseller);

        View::render('owner/monitor', [
            'title' => 'کاربران آنلاین',
            'onlineTotal' => $onlineTotal,
            'perReseller' => $perReseller,
            'resellers' => array_column($resellers, null, 'id'),
            'error' => $error,
        ]);
    }

    private function isOnline(array $user): bool
    {
        if (!empty($user['isOnline'])) {
            return true;
        }
        foreach (['onlineAt', 'lastOnlineAt', 'lastConnectedAt'] as $k) {
            if (!empty($user[$k])) {
                return (time() - strtotime((string) $user[$k])) < 180; // within 3 min
            }
        }
        return false;
    }
}

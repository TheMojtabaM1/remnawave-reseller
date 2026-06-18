<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Response;
use App\Core\View;
use App\Services\RemnawaveClient;
use App\Services\RemnawaveException;

final class NodeController
{
    public function index(): void
    {
        $nodes = [];
        $error = null;
        try {
            $nodes = (new RemnawaveClient())->listNodes();
        } catch (RemnawaveException $e) {
            $error = $e->getMessage();
        }
        View::render('owner/nodes', ['title' => 'سلامت نودها', 'nodes' => $nodes, 'error' => $error]);
    }

    /** Live JSON snapshot polled by the dashboard every few seconds. */
    public function live(): void
    {
        $rw = new RemnawaveClient();
        try {
            $nodes = $rw->listNodes();
        } catch (RemnawaveException $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 200);
        }
        $val = fn(array $n, array $keys, $d = null) => (function () use ($n, $keys, $d) {
            foreach ($keys as $k) {
                if (isset($n[$k]) && $n[$k] !== null) {
                    return $n[$k];
                }
            }
            return $d;
        })();

        $out = [];
        $totalOnlineUsers = 0;
        foreach ($nodes as $n) {
            $online = (bool) ($n['isNodeOnline'] ?? $n['isConnected'] ?? $n['isOnline'] ?? false);
            $users = (int) ($n['usersOnline'] ?? $n['onlineUsers'] ?? 0);
            $totalOnlineUsers += $users;
            $out[] = [
                'name'   => (string) ($n['name'] ?? $n['nodeName'] ?? '—'),
                'online' => $online,
                'users'  => $users,
                'used'   => (int) ($n['trafficUsedBytes'] ?? $n['usedTrafficBytes'] ?? 0),
            ];
        }

        Response::json([
            'ok' => true,
            'online' => $rw->onlineCount(),
            'nodeUsers' => $totalOnlineUsers,
            'nodes' => $out,
            't' => gmdate('H:i:s'),
        ]);
    }
}

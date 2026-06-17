<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

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
}

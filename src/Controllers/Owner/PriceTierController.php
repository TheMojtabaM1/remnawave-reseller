<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditLogger;

final class PriceTierController
{
    public function index(): void
    {
        $tiers = Db::all(
            'SELECT pt.*, p.name AS plan_name FROM price_tiers pt
             LEFT JOIN plans p ON p.id = pt.plan_id
             ORDER BY pt.scope, pt.plan_id, pt.min_gb'
        );
        $plans = Db::all('SELECT id, name FROM plans ORDER BY name');
        View::render('owner/pricing', ['title' => 'قیمت‌گذاری پلکانی', 'tiers' => $tiers, 'plans' => $plans]);
    }

    public function store(Request $request): void
    {
        $scope = $request->post('scope') === 'plan' ? 'plan' : 'global';
        $planId = $scope === 'plan' ? (int) $request->post('plan_id') : null;
        $minGb = (int) $request->post('min_gb');
        $price = (int) $request->post('price_per_gb');

        if ($minGb < 0 || $price < 0 || ($scope === 'plan' && !$planId)) {
            flash('error', 'مقادیر نامعتبر است.');
            Response::redirect('/owner/pricing');
        }
        Db::insert(
            'INSERT INTO price_tiers (scope, plan_id, min_gb, price_per_gb) VALUES (:s,:p,:m,:pr)',
            [':s' => $scope, ':p' => $planId, ':m' => $minGb, ':pr' => $price]
        );
        AuditLogger::log('pricetier.create', 'price_tier', null, ['scope' => $scope, 'min_gb' => $minGb]);
        flash('success', 'پله قیمتی اضافه شد.');
        Response::redirect('/owner/pricing');
    }

    public function destroy(Request $request, array $args): void
    {
        Db::exec('DELETE FROM price_tiers WHERE id=:id', [':id' => (int) $args['id']]);
        AuditLogger::log('pricetier.delete', 'price_tier', (int) $args['id']);
        flash('success', 'پله قیمتی حذف شد.');
        Response::redirect('/owner/pricing');
    }
}

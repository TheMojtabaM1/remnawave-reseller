<?php

declare(strict_types=1);

namespace App\Controllers\Reseller;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\ConfigService;
use App\Services\PricingService;
use App\Services\RemnawaveClient;
use App\Services\RemnawaveException;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

final class ConfigController
{
    public function index(Request $request): void
    {
        $r = Auth::reseller();
        $id = (int) $r['id'];
        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');
        $page = max(1, $request->int('page', 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $where = ['reseller_id = :id', 'status <> "deleted"'];
        $params = [':id' => $id];
        if ($q !== '') {
            // Distinct placeholders: native PDO can't reuse one name twice.
            $where[] = '(remnawave_username LIKE :q1 OR subscription_url LIKE :q2)';
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
        }
        if (in_array($status, ['active', 'disabled', 'expired'], true)) {
            $where[] = 'status = :st';
            $params[':st'] = $status;
        }
        $w = implode(' AND ', $where);

        $total = (int) Db::scalar("SELECT COUNT(*) FROM configs WHERE {$w}", $params);
        $configs = Db::all("SELECT * FROM configs WHERE {$w} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}", $params);

        // Live-refresh usage for rows not synced in the last 2 minutes (the
        // list/by-tag API omits usage; only the detailed user GET has it).
        $configs = $this->refreshUsage($configs);

        View::render('reseller/configs/index', [
            'title' => 'کانفیگ‌ها',
            'configs' => $configs,
            'q' => $q, 'status' => $status,
            'page' => $page, 'pages' => (int) ceil($total / $perPage),
            'perm' => fn($k) => ConfigService::perm($r, $k),
        ], 'reseller');
    }

    /** Refresh used-traffic for stale rows via the detailed user GET (bounded). */
    private function refreshUsage(array $configs): array
    {
        $stale = array_filter($configs, fn($c) => $c['remnawave_uuid']
            && (empty($c['last_synced_at']) || strtotime((string) $c['last_synced_at']) < time() - 120));
        if (!$stale) {
            return $configs;
        }
        try {
            $rw = new RemnawaveClient();
        } catch (\Throwable) {
            return $configs;
        }
        $fresh = [];
        $n = 0;
        foreach ($stale as $c) {
            if ($n++ >= 30) {
                break;
            }
            try {
                $user = $rw->getUser((string) $c['remnawave_uuid']);
            } catch (RemnawaveException) {
                continue;
            }
            $used = $rw->usedBytes($user);
            $status = ($rw->userExpired($user) && $c['status'] === 'active') ? 'expired' : $c['status'];
            Db::exec('UPDATE configs SET last_used_bytes=:u, status=:s, last_synced_at=UTC_TIMESTAMP() WHERE id=:id', [':u' => $used, ':s' => $status, ':id' => $c['id']]);
            $fresh[$c['id']] = ['last_used_bytes' => $used, 'status' => $status];
        }
        foreach ($configs as &$c) {
            if (isset($fresh[$c['id']])) {
                $c['last_used_bytes'] = $fresh[$c['id']]['last_used_bytes'];
                $c['status'] = $fresh[$c['id']]['status'];
            }
        }
        return $configs;
    }

    public function create(): void
    {
        $r = Auth::reseller();
        if (!ConfigService::perm($r, 'can_create_config')) {
            flash('error', 'شما اجازه ساخت کانفیگ ندارید.');
            Response::redirect('/panel/configs');
        }

        $plans = Db::all('SELECT * FROM plans WHERE status="active" ORDER BY price');
        $templates = Db::all('SELECT * FROM config_templates ORDER BY name');

        View::render('reseller/configs/create', [
            'title' => 'ساخت کانفیگ',
            'r' => $r,
            'plans' => $plans,
            'templates' => $templates,
            'squads' => $this->allowedSquads($r),
            'canCustom' => ConfigService::perm($r, 'can_create_custom'),
        ], 'reseller');
    }

    public function store(Request $request): void
    {
        $r = Auth::reseller();
        $source = (string) $request->post('source', 'plan'); // plan | template | custom

        try {
            $opts = $this->buildOpts($request, $r, $source, $isCustom);
            ConfigService::validateCreate($r, $opts['volume_gb'], $opts['days'], $opts['squads'], $isCustom);

            $svc = new ConfigService(new RemnawaveClient());
            $result = $svc->create($r, $opts);

            flash('success', 'کانفیگ ساخته شد: ' . $result['username']);
            Response::redirect('/panel/configs/' . $result['config_id']);
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
            flash_old($request->all());
            Response::redirect('/panel/configs/create');
        } catch (RemnawaveException $e) {
            flash('error', 'خطای Remnawave: ' . $e->getMessage());
            Response::redirect('/panel/configs/create');
        } catch (\Throwable $e) {
            error_log('[config.store] ' . $e->getMessage());
            flash('error', 'خطای غیرمنتظره در ساخت کانفیگ.');
            Response::redirect('/panel/configs/create');
        }
    }

    public function show(Request $request, array $args): void
    {
        $r = Auth::reseller();
        $c = $this->own((int) $args['id'], $r);

        // Refresh live usage (best-effort).
        try {
            (new ConfigService(new RemnawaveClient()))->syncUsage($c);
            $c = $this->own((int) $args['id'], $r);
        } catch (RemnawaveException) {
        }

        View::render('reseller/configs/show', [
            'title' => 'کانفیگ ' . $c['remnawave_username'],
            'r' => $r,
            'c' => $c,
            'perm' => fn($k) => ConfigService::perm($r, $k),
        ], 'reseller');
    }

    public function renew(Request $request, array $args): void
    {
        $r = Auth::reseller();
        $c = $this->own((int) $args['id'], $r);
        $addDays = max(0, (int) $request->post('add_days', 0));
        $addGb = max(0, (int) $request->post('add_gb', 0));
        if ($addDays === 0 && $addGb === 0) {
            flash('error', 'مقدار تمدید را وارد کنید.');
            Response::redirect('/panel/configs/' . $c['id']);
        }

        // Price = custom price for the added volume/days.
        $price = PricingService::customPrice($addGb, $addDays, $r, $c['plan_id'] ? (int) $c['plan_id'] : null);
        try {
            (new ConfigService(new RemnawaveClient()))->renew($r, $c, $addDays, $addGb, $price);
            flash('success', 'کانفیگ تمدید شد. مبلغ کسرشده: ' . toman($price));
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
        } catch (RemnawaveException $e) {
            flash('error', 'خطای Remnawave: ' . $e->getMessage());
        }
        Response::redirect('/panel/configs/' . $c['id']);
    }

    public function toggle(Request $request, array $args): void
    {
        $r = Auth::reseller();
        $c = $this->own((int) $args['id'], $r);
        try {
            $new = (new ConfigService(new RemnawaveClient()))->toggle($r, $c);
            flash('success', $new === 'active' ? 'کانفیگ فعال شد.' : 'کانفیگ غیرفعال شد.');
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
        } catch (RemnawaveException $e) {
            flash('error', 'خطای Remnawave: ' . $e->getMessage());
        }
        Response::redirect('/panel/configs/' . $c['id']);
    }

    public function regenerate(Request $request, array $args): void
    {
        $r = Auth::reseller();
        $c = $this->own((int) $args['id'], $r);
        try {
            (new ConfigService(new RemnawaveClient()))->regenerate($r, $c);
            flash('success', 'لینک اشتراک بازتولید شد.');
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
        } catch (RemnawaveException $e) {
            flash('error', 'خطای Remnawave: ' . $e->getMessage());
        }
        Response::redirect('/panel/configs/' . $c['id']);
    }

    public function destroy(Request $request, array $args): void
    {
        $r = Auth::reseller();
        $c = $this->own((int) $args['id'], $r);
        try {
            $refund = (new ConfigService(new RemnawaveClient()))->delete($r, $c);
            flash('success', $refund > 0 ? 'کانفیگ حذف شد. بازگشت وجه: ' . toman($refund) : 'کانفیگ حذف شد.');
        } catch (\DomainException $e) {
            flash('error', $e->getMessage());
        } catch (RemnawaveException $e) {
            flash('error', 'خطای Remnawave: ' . $e->getMessage());
        }
        Response::redirect('/panel/configs');
    }

    public function qr(Request $request, array $args): void
    {
        $r = Auth::reseller();
        $c = $this->own((int) $args['id'], $r);
        $url = (string) $c['subscription_url'];
        if ($url === '') {
            Response::abort(404, 'لینک اشتراک موجود نیست');
        }
        $result = (new PngWriter())->write(new QrCode($url));
        header('Content-Type: ' . $result->getMimeType());
        echo $result->getString();
        exit;
    }

    /** Bulk operations on the reseller's own configs. */
    public function bulk(Request $request): void
    {
        $r = Auth::reseller();
        $ids = array_map('intval', (array) $request->arr('config_ids'));
        $op = (string) $request->post('operation');
        if (!$ids) {
            flash('error', 'موردی انتخاب نشده است.');
            Response::redirect('/panel/configs');
        }
        $svc = new ConfigService(new RemnawaveClient());
        $ok = 0; $fail = 0;
        foreach ($ids as $id) {
            $c = Db::one('SELECT * FROM configs WHERE id=:id AND reseller_id=:r', [':id' => $id, ':r' => $r['id']]);
            if (!$c || $c['status'] === 'deleted') {
                $fail++;
                continue;
            }
            try {
                match ($op) {
                    'disable' => ($c['status'] === 'active' ? $svc->toggle($r, $c) : null),
                    'enable' => ($c['status'] === 'disabled' ? $svc->toggle($r, $c) : null),
                    'regenerate' => $svc->regenerate($r, $c),
                    'delete' => $svc->delete($r, $c),
                    default => throw new \DomainException('عملیات نامعتبر'),
                };
                $ok++;
            } catch (\Throwable $e) {
                $fail++;
            }
        }
        flash($fail ? 'warning' : 'success', "عملیات گروهی: موفق {$ok} / ناموفق {$fail}.");
        Response::redirect('/panel/configs');
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function own(int $id, array $r): array
    {
        $c = Db::one('SELECT * FROM configs WHERE id=:id', [':id' => $id]);
        if (!$c || (int) $c['reseller_id'] !== (int) $r['id'] || $c['status'] === 'deleted') {
            Response::abort(404, 'کانفیگ یافت نشد');
        }
        return $c;
    }

    /** Build create options from the request based on source. */
    private function buildOpts(Request $request, array $r, string $source, ?bool &$isCustom): array
    {
        $isCustom = false;
        if ($source === 'plan') {
            $plan = Db::one('SELECT * FROM plans WHERE id=:id AND status="active"', [':id' => (int) $request->post('plan_id')]);
            if (!$plan) {
                throw new \DomainException('پلن انتخابی نامعتبر است.');
            }
            return [
                'volume_gb' => (int) $plan['volume_gb'],
                'days' => (int) $plan['duration_days'],
                'squads' => json_decode((string) ($plan['allowed_squads'] ?? '[]'), true) ?: $this->squadIds($r),
                'hwid_limit' => (int) $plan['hwid_limit'] ?: (int) $r['hwid_device_limit'],
                'traffic_strategy' => $plan['traffic_strategy'],
                'price' => PricingService::planPrice($plan, $r),
                'per_gb_rate' => 0,
                'plan_id' => (int) $plan['id'],
                'is_trial' => (int) $plan['is_trial'],
            ];
        }
        if ($source === 'template') {
            $t = Db::one('SELECT * FROM config_templates WHERE id=:id', [':id' => (int) $request->post('template_id')]);
            if (!$t) {
                throw new \DomainException('قالب انتخابی نامعتبر است.');
            }
            $vol = (int) $t['volume_gb'];
            $days = (int) $t['duration_days'];
            return [
                'volume_gb' => $vol,
                'days' => $days,
                'squads' => json_decode((string) ($t['squads'] ?? '[]'), true) ?: $this->squadIds($r),
                'hwid_limit' => (int) $t['hwid_limit'] ?: (int) $r['hwid_device_limit'],
                'traffic_strategy' => $t['traffic_strategy'],
                'price' => PricingService::customPrice($vol, $days, $r),
                'per_gb_rate' => PricingService::perGbRate($vol, $r),
                'template_id' => (int) $t['id'],
            ];
        }

        // custom
        $isCustom = true;
        $vol = (int) $request->post('volume_gb');
        $days = (int) $request->post('days');
        $squads = array_values(array_filter((array) $request->arr('squads')));
        return [
            'volume_gb' => $vol,
            'days' => $days,
            'squads' => $squads,
            'hwid_limit' => (int) $r['hwid_device_limit'],
            'traffic_strategy' => config_value('default_traffic_strategy', 'NO_RESET'),
            'price' => PricingService::customPrice($vol, $days, $r),
            'per_gb_rate' => PricingService::perGbRate($vol, $r),
        ];
    }

    private function squadIds(array $r): array
    {
        $allowed = ConfigService::allowedSquads($r);
        if ($allowed) {
            return $allowed;
        }
        // No restriction: fall back to all live squads.
        return array_column($this->allowedSquads($r), 'uuid');
    }

    /** Squads the reseller may use (intersection with live list). */
    private function allowedSquads(array $r): array
    {
        try {
            $live = (new RemnawaveClient())->listInternalSquads();
        } catch (RemnawaveException) {
            return [];
        }
        $allowed = ConfigService::allowedSquads($r);
        if (!$allowed) {
            return $live;
        }
        return array_values(array_filter($live, fn($s) => in_array($s['uuid'], $allowed, true)));
    }
}

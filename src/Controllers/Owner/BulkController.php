<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Db;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditLogger;
use App\Services\ConfigService;
use App\Services\PricingService;
use App\Services\RemnawaveClient;
use App\Services\RemnawaveException;
use App\Services\WalletService;

final class BulkController
{
    public function index(): void
    {
        $resellers = Db::all('SELECT id, username, display_name FROM resellers ORDER BY username');
        $plans = Db::all('SELECT * FROM plans WHERE status = "active" ORDER BY name');
        $templates = Db::all('SELECT * FROM config_templates ORDER BY name');
        $configs = Db::all(
            'SELECT c.*, r.username AS reseller_username FROM configs c
             JOIN resellers r ON r.id = c.reseller_id
             WHERE c.status IN ("active","disabled") ORDER BY c.created_at DESC LIMIT 500'
        );
        View::render('owner/bulk', [
            'title' => 'عملیات گروهی',
            'resellers' => $resellers,
            'plans' => $plans,
            'templates' => $templates,
            'configs' => $configs,
        ]);
    }

    /** Apply an action to selected config IDs. */
    public function action(Request $request): void
    {
        $ids = array_map('intval', (array) $request->arr('config_ids'));
        $op = (string) $request->post('operation');
        if (!$ids) {
            flash('error', 'هیچ کانفیگی انتخاب نشده است.');
            Response::redirect('/owner/bulk');
        }

        $rw = new RemnawaveClient();
        $svc = new ConfigService($rw);
        $ok = 0; $fail = 0;

        foreach ($ids as $id) {
            $c = Db::one('SELECT * FROM configs WHERE id = :id', [':id' => $id]);
            if (!$c || $c['status'] === 'deleted') {
                $fail++;
                continue;
            }
            try {
                switch ($op) {
                    case 'enable':
                        $rw->updateUser((string) $c['remnawave_uuid'], ['status' => 'ACTIVE']);
                        Db::exec('UPDATE configs SET status="active" WHERE id=:id', [':id' => $id]);
                        break;
                    case 'disable':
                        $rw->updateUser((string) $c['remnawave_uuid'], ['status' => 'DISABLED']);
                        Db::exec('UPDATE configs SET status="disabled" WHERE id=:id', [':id' => $id]);
                        break;
                    case 'regenerate':
                        $sub = $rw->subscriptionUrl($rw->revokeSubscription((string) $c['remnawave_uuid']));
                        if ($sub) {
                            Db::exec('UPDATE configs SET subscription_url=:s WHERE id=:id', [':s' => $sub, ':id' => $id]);
                        }
                        break;
                    case 'extend':
                        $days = max(1, (int) $request->post('extend_days', 30));
                        $this->extend($rw, $c, $days);
                        break;
                    case 'delete':
                        $reseller = Db::one('SELECT * FROM resellers WHERE id=:id', [':id' => $c['reseller_id']]);
                        $this->deleteWithRefund($rw, $c, $reseller);
                        break;
                    default:
                        $fail++;
                        continue 2;
                }
                $ok++;
            } catch (\Throwable $e) {
                $fail++;
                error_log('[bulk] config ' . $id . ': ' . $e->getMessage());
            }
        }

        AuditLogger::log('bulk.action', 'config', null, ['operation' => $op, 'ok' => $ok, 'fail' => $fail]);
        flash($fail ? 'warning' : 'success', "عملیات «{$op}» انجام شد: موفق {$ok} / ناموفق {$fail}.");
        Response::redirect('/owner/bulk');
    }

    /** Bulk-create N configs from a plan or template for a reseller. */
    public function bulkCreate(Request $request): void
    {
        $resellerId = (int) $request->post('reseller_id');
        $count = max(1, min(100, (int) $request->post('count', 1)));
        $source = (string) $request->post('source'); // plan:ID | template:ID

        $reseller = Db::one('SELECT * FROM resellers WHERE id=:id', [':id' => $resellerId]);
        if (!$reseller) {
            flash('error', 'نماینده نامعتبر است.');
            Response::redirect('/owner/bulk');
        }

        [$type, $sid] = array_pad(explode(':', $source, 2), 2, '');
        $opts = $this->sourceOpts($type, (int) $sid, $reseller);
        if ($opts === null) {
            flash('error', 'منبع (پلن/قالب) نامعتبر است.');
            Response::redirect('/owner/bulk');
        }

        $svc = new ConfigService(new RemnawaveClient());
        $ok = 0; $fail = 0; $lastErr = '';
        for ($i = 0; $i < $count; $i++) {
            try {
                $svc->create($reseller, $opts);
                $reseller = Db::one('SELECT * FROM resellers WHERE id=:id', [':id' => $resellerId]); // refresh balance
                $ok++;
            } catch (\Throwable $e) {
                $fail++;
                $lastErr = $e->getMessage();
                break; // stop on first failure (likely balance/limit)
            }
        }

        AuditLogger::log('bulk.create', 'reseller', $resellerId, ['count' => $ok, 'source' => $source]);
        flash($fail ? 'warning' : 'success', "ساخت گروهی: {$ok} کانفیگ ایجاد شد." . ($lastErr ? " توقف: {$lastErr}" : ''));
        Response::redirect('/owner/resellers/' . $resellerId);
    }

    /** Restore an archived (deleted) config by re-provisioning it (#170). */
    public function restore(Request $request, array $args): void
    {
        $c = Db::one('SELECT * FROM configs WHERE id=:id AND status="deleted"', [':id' => (int) $args['id']]);
        if (!$c) {
            flash('error', 'کانفیگ بایگانی‌شده یافت نشد.');
            redirect_back('/owner/resellers');
        }
        $reseller = Db::one('SELECT * FROM resellers WHERE id=:id', [':id' => $c['reseller_id']]);
        if (!$reseller) {
            flash('error', 'نماینده یافت نشد.');
            redirect_back('/owner/resellers');
        }

        // Squads: forced > plan > template > reseller-allowed > first live squad.
        $rw = new RemnawaveClient();
        $squads = ConfigService::forcedSquads($reseller);
        if (!$squads && $c['plan_id']) {
            $squads = json_decode((string) (Db::scalar('SELECT allowed_squads FROM plans WHERE id=:id', [':id' => $c['plan_id']]) ?? '[]'), true) ?: [];
        }
        if (!$squads && $c['template_id']) {
            $squads = json_decode((string) (Db::scalar('SELECT squads FROM config_templates WHERE id=:id', [':id' => $c['template_id']]) ?? '[]'), true) ?: [];
        }
        if (!$squads) {
            $squads = ConfigService::allowedSquads($reseller);
        }
        if (!$squads) {
            try {
                $live = $rw->listInternalSquads();
                $squads = $live ? [$live[0]['uuid']] : [];
            } catch (\Throwable) {
            }
        }

        $days = max(1, (int) $c['duration_days']);
        try {
            $rwUser = $rw->createUser([
                'username'             => $c['remnawave_username'],
                'status'               => 'ACTIVE',
                'trafficLimitBytes'    => gb_to_bytes((int) $c['volume_gb']),
                'trafficLimitStrategy' => config_value('default_traffic_strategy', 'NO_RESET'),
                'expireAt'             => iso8601_from_days($days),
                'hwidDeviceLimit'      => (int) $reseller['hwid_device_limit'],
                'description'          => 'Reseller restore #' . $reseller['id'],
                'tag'                  => ConfigService::tag((int) $reseller['id']),
                'squads'               => array_values($squads),
            ]);
        } catch (RemnawaveException $e) {
            flash('error', 'بازیابی ناموفق: ' . $e->getMessage());
            redirect_back('/owner/resellers/' . $reseller['id']);
        }

        $sub = $rw->subscriptionUrl($rwUser);
        $exp = (new \DateTime("+{$days} days", new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        Db::exec(
            'UPDATE configs SET remnawave_uuid=:uuid, subscription_url=:sub, status="active",
                    expires_at=:exp, last_used_bytes=0, last_synced_at=UTC_TIMESTAMP() WHERE id=:id',
            [':uuid' => (string) ($rwUser['uuid'] ?? ''), ':sub' => $sub, ':exp' => $exp, ':id' => $c['id']]
        );
        AuditLogger::log('config.restore', 'config', (int) $c['id'], ['username' => $c['remnawave_username']]);
        flash('success', 'کانفیگ بازیابی شد: ' . $c['remnawave_username'] . ' (بدون کسر هزینه).');
        redirect_back('/owner/resellers/' . $reseller['id']);
    }

    // ── helpers ──────────────────────────────────────────────────────

    private function extend(RemnawaveClient $rw, array $c, int $days): void
    {
        $base = $c['expires_at'] && strtotime($c['expires_at']) > time()
            ? new \DateTime($c['expires_at'], new \DateTimeZone('UTC'))
            : new \DateTime('now', new \DateTimeZone('UTC'));
        $base->modify("+{$days} days");
        $rw->updateUser((string) $c['remnawave_uuid'], [
            'expireAt' => $base->format('Y-m-d\TH:i:s.000\Z'),
            'status' => 'ACTIVE',
        ]);
        Db::exec(
            'UPDATE configs SET expires_at=:e, duration_days = duration_days + :d, status="active" WHERE id=:id',
            [':e' => $base->format('Y-m-d H:i:s'), ':d' => $days, ':id' => $c['id']]
        );
    }

    private function deleteWithRefund(RemnawaveClient $rw, array $c, ?array $reseller): void
    {
        $used = (int) $c['last_used_bytes'];
        try {
            $used = $rw->getUserUsage((string) $c['remnawave_uuid']);
        } catch (\Throwable) {
        }
        $refund = ConfigService::refundAmount((int) $c['volume_gb'], $used, (int) $c['price_charged']);
        try {
            $rw->deleteUser((string) $c['remnawave_uuid']);
        } catch (\Throwable) {
        }
        Db::exec('UPDATE configs SET status="deleted", last_used_bytes=:u, last_synced_at=UTC_TIMESTAMP() WHERE id=:id', [':u' => $used, ':id' => $c['id']]);
        if ($refund > 0 && $reseller) {
            WalletService::refund((int) $reseller['id'], $refund, 'بازگشت وجه حذف گروهی ' . $c['remnawave_username'], (int) $c['id']);
        }
    }

    private function sourceOpts(string $type, int $sid, array $reseller): ?array
    {
        if ($type === 'plan') {
            $plan = Db::one('SELECT * FROM plans WHERE id=:id', [':id' => $sid]);
            if (!$plan) {
                return null;
            }
            return [
                'volume_gb' => (int) $plan['volume_gb'],
                'days' => (int) $plan['duration_days'],
                'squads' => json_decode((string) ($plan['allowed_squads'] ?? '[]'), true) ?: [],
                'hwid_limit' => (int) $plan['hwid_limit'],
                'traffic_strategy' => $plan['traffic_strategy'],
                'price' => PricingService::planPrice($plan, $reseller),
                'per_gb_rate' => 0,
                'plan_id' => (int) $plan['id'],
                'is_trial' => (int) $plan['is_trial'],
            ];
        }
        if ($type === 'template') {
            $t = Db::one('SELECT * FROM config_templates WHERE id=:id', [':id' => $sid]);
            if (!$t) {
                return null;
            }
            $vol = (int) $t['volume_gb'];
            $days = (int) $t['duration_days'];
            return [
                'volume_gb' => $vol,
                'days' => $days,
                'squads' => json_decode((string) ($t['squads'] ?? '[]'), true) ?: [],
                'hwid_limit' => (int) $t['hwid_limit'],
                'traffic_strategy' => $t['traffic_strategy'],
                'price' => PricingService::customPrice($vol, $days, $reseller),
                'per_gb_rate' => PricingService::perGbRate($vol, $reseller),
                'template_id' => (int) $t['id'],
            ];
        }
        return null;
    }
}

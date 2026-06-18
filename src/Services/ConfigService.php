<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

/**
 * Reseller config lifecycle with server-side limit/permission enforcement.
 * A "config" is a real Remnawave user tagged RSL_<reseller_id>.
 *
 * Throws \DomainException (Persian message) on any rule violation; the
 * controller catches and flashes the message.
 */
final class ConfigService
{
    public function __construct(private RemnawaveClient $rw)
    {
    }

    // ── Permission / limit helpers ──────────────────────────────────

    public static function perm(array $reseller, string $key): bool
    {
        $perms = self::json($reseller['permissions']);
        // Default-allow core actions only if explicitly enabled.
        return !empty($perms[$key]);
    }

    public static function allowedSquads(array $reseller): array
    {
        return self::json($reseller['allowed_squads']);
    }

    private static function json(mixed $val): array
    {
        if (is_array($val)) {
            return $val;
        }
        if (is_string($val) && $val !== '') {
            $d = json_decode($val, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }

    private static function activeCount(int $resellerId): int
    {
        return (int) Db::scalar(
            "SELECT COUNT(*) FROM configs WHERE reseller_id = :id AND status IN ('active','disabled')",
            [':id' => $resellerId]
        );
    }

    private static function createdTodayCount(int $resellerId): int
    {
        return (int) Db::scalar(
            'SELECT COUNT(*) FROM configs WHERE reseller_id = :id AND DATE(created_at) = UTC_DATE()',
            [':id' => $resellerId]
        );
    }

    private static function allocatedGb(int $resellerId): int
    {
        return (int) Db::scalar(
            "SELECT COALESCE(SUM(volume_gb),0) FROM configs WHERE reseller_id = :id AND status IN ('active','disabled')",
            [':id' => $resellerId]
        );
    }

    /**
     * Validate a create request against all reseller limits. Throws on failure.
     * @param array $squads list of squad UUIDs to assign
     */
    public static function validateCreate(array $reseller, int $volumeGb, int $days, array $squads, bool $isCustom): void
    {
        if (!self::perm($reseller, 'can_create_config')) {
            throw new \DomainException('شما اجازه ایجاد کانفیگ ندارید.');
        }
        if ($isCustom && !self::perm($reseller, 'can_create_custom')) {
            throw new \DomainException('شما اجازه ایجاد کانفیگ سفارشی ندارید؛ فقط از پلن‌ها استفاده کنید.');
        }

        $max = (int) $reseller['max_users'];
        if ($max > 0 && self::activeCount((int) $reseller['id']) >= $max) {
            throw new \DomainException('به سقف تعداد کانفیگ مجاز رسیده‌اید (' . $max . ').');
        }

        $perDay = (int) $reseller['max_users_per_day'];
        if ($perDay > 0 && self::createdTodayCount((int) $reseller['id']) >= $perDay) {
            throw new \DomainException('به سقف ایجاد کانفیگ روزانه رسیده‌اید (' . $perDay . ').');
        }

        $minV = (int) $reseller['min_volume_gb'];
        $maxV = (int) $reseller['max_volume_gb'];
        if ($minV > 0 && $volumeGb < $minV) {
            throw new \DomainException('حجم نباید کمتر از ' . $minV . ' گیگابایت باشد.');
        }
        if ($maxV > 0 && $volumeGb > $maxV) {
            throw new \DomainException('حجم نباید بیشتر از ' . $maxV . ' گیگابایت باشد.');
        }

        $minD = (int) $reseller['min_days'];
        $maxD = (int) $reseller['max_days'];
        if ($minD > 0 && $days < $minD) {
            throw new \DomainException('مدت نباید کمتر از ' . $minD . ' روز باشد.');
        }
        if ($maxD > 0 && $days > $maxD) {
            throw new \DomainException('مدت نباید بیشتر از ' . $maxD . ' روز باشد.');
        }

        $pool = (int) $reseller['max_total_traffic_gb'];
        if ($pool > 0 && (self::allocatedGb((int) $reseller['id']) + $volumeGb) > $pool) {
            throw new \DomainException('این کانفیگ از سقف کل ترافیک مجاز شما عبور می‌کند (سقف: ' . $pool . ' گیگ).');
        }

        // Squad whitelist: empty reseller list = unrestricted.
        $allowed = self::allowedSquads($reseller);
        if ($allowed) {
            foreach ($squads as $uuid) {
                if (!in_array($uuid, $allowed, true)) {
                    throw new \DomainException('یکی از Squadهای انتخابی مجاز شما نیست.');
                }
            }
        }
        if (empty($squads)) {
            throw new \DomainException('حداقل یک Squad باید انتخاب شود.');
        }
        if ($volumeGb <= 0 || $days <= 0) {
            throw new \DomainException('حجم و مدت باید بزرگ‌تر از صفر باشند.');
        }
    }

    // ── Create ──────────────────────────────────────────────────────

    /**
     * @param array $opts volume_gb, days, squads[], hwid_limit, traffic_strategy,
     *                    price, per_gb_rate, plan_id, template_id, is_trial
     * @return array {config_id, subscription_url, username, uuid}
     */
    public function create(array $reseller, array $opts): array
    {
        $volumeGb = (int) $opts['volume_gb'];
        $days     = (int) $opts['days'];
        $squads   = array_values($opts['squads']);
        $price    = (int) $opts['price'];

        if (!WalletService::canAfford($reseller, $price)) {
            throw new \DomainException(WalletService::affordError($reseller, $price));
        }

        $username = $this->makeUsername($reseller['prefix'], $opts['custom_name'] ?? null);
        $rwUser = $this->rw->createUser([
            'username'             => $username,
            'status'               => 'ACTIVE',
            'trafficLimitBytes'    => gb_to_bytes($volumeGb),
            'trafficLimitStrategy' => $opts['traffic_strategy'] ?? 'NO_RESET',
            'expireAt'             => iso8601_from_days($days),
            'hwidDeviceLimit'      => (int) ($opts['hwid_limit'] ?? $reseller['hwid_device_limit']),
            'description'          => 'USVSIR reseller #' . $reseller['id'] . ' (' . $reseller['username'] . ')',
            'tag'                  => self::tag((int) $reseller['id']),
            'squads'               => $squads,
        ]);

        $uuid = (string) ($rwUser['uuid'] ?? '');
        $subUrl = $this->rw->subscriptionUrl($rwUser);
        $rwUsername = (string) ($rwUser['username'] ?? $username);
        $expiresAt = (new \DateTime("+{$days} days", new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        // Persist + charge atomically (wallet charge has its own tx; we insert config first).
        $configId = Db::insert(
            'INSERT INTO configs
                (reseller_id, plan_id, template_id, remnawave_uuid, remnawave_username, subscription_url,
                 volume_gb, duration_days, per_gb_rate, price_charged, is_trial, status, created_at, expires_at, last_synced_at)
             VALUES (:rid, :pid, :tid, :uuid, :uname, :sub, :vol, :days, :rate, :price, :trial, "active", UTC_TIMESTAMP(), :exp, UTC_TIMESTAMP())',
            [
                ':rid' => $reseller['id'], ':pid' => $opts['plan_id'] ?? null, ':tid' => $opts['template_id'] ?? null,
                ':uuid' => $uuid, ':uname' => $rwUsername, ':sub' => $subUrl,
                ':vol' => $volumeGb, ':days' => $days, ':rate' => (int) ($opts['per_gb_rate'] ?? 0),
                ':price' => $price, ':trial' => !empty($opts['is_trial']) ? 1 : 0, ':exp' => $expiresAt,
            ]
        );

        if ($price > 0) {
            WalletService::charge((int) $reseller['id'], $price, 'ایجاد کانفیگ ' . $rwUsername, $configId);
        }

        AuditLogger::log('config.create', 'config', $configId, [
            'username' => $rwUsername, 'volume_gb' => $volumeGb, 'days' => $days, 'price' => $price,
        ]);

        return ['config_id' => $configId, 'subscription_url' => $subUrl, 'username' => $rwUsername, 'uuid' => $uuid];
    }

    // ── Renew / extend ──────────────────────────────────────────────

    public function renew(array $reseller, array $config, int $addDays, int $addVolumeGb, int $price): void
    {
        if (!self::perm($reseller, 'can_renew')) {
            throw new \DomainException('شما اجازه تمدید ندارید.');
        }
        if (!WalletService::canAfford($reseller, $price)) {
            throw new \DomainException(WalletService::affordError($reseller, $price));
        }

        $uuid = (string) $config['remnawave_uuid'];
        $base = $config['expires_at'] && strtotime($config['expires_at']) > time()
            ? new \DateTime($config['expires_at'], new \DateTimeZone('UTC'))
            : new \DateTime('now', new \DateTimeZone('UTC'));
        $base->modify("+{$addDays} days");
        $newExpire = $base->format('Y-m-d\TH:i:s.000\Z');
        $newVolumeGb = (int) $config['volume_gb'] + $addVolumeGb;

        $this->rw->updateUser($uuid, [
            'expireAt'          => $newExpire,
            'trafficLimitBytes' => gb_to_bytes($newVolumeGb),
            'status'            => 'ACTIVE',
        ]);

        Db::exec(
            'UPDATE configs SET expires_at = :exp, volume_gb = :vol, duration_days = duration_days + :d,
                    price_charged = price_charged + :p, status = "active" WHERE id = :id',
            [
                ':exp' => $base->format('Y-m-d H:i:s'), ':vol' => $newVolumeGb,
                ':d' => $addDays, ':p' => $price, ':id' => $config['id'],
            ]
        );

        if ($price > 0) {
            WalletService::charge((int) $reseller['id'], $price, 'تمدید کانفیگ ' . $config['remnawave_username'], (int) $config['id']);
        }
        AuditLogger::log('config.renew', 'config', (int) $config['id'], ['add_days' => $addDays, 'add_gb' => $addVolumeGb, 'price' => $price]);
    }

    // ── Enable / disable ────────────────────────────────────────────

    public function toggle(array $reseller, array $config): string
    {
        if (!self::perm($reseller, 'can_edit_config')) {
            throw new \DomainException('شما اجازه ویرایش کانفیگ ندارید.');
        }
        $disable = $config['status'] === 'active';
        $this->rw->updateUser((string) $config['remnawave_uuid'], ['status' => $disable ? 'DISABLED' : 'ACTIVE']);
        $new = $disable ? 'disabled' : 'active';
        Db::exec('UPDATE configs SET status = :s WHERE id = :id', [':s' => $new, ':id' => $config['id']]);
        AuditLogger::log('config.toggle', 'config', (int) $config['id'], ['status' => $new]);
        return $new;
    }

    // ── Regenerate subscription ─────────────────────────────────────

    public function regenerate(array $reseller, array $config): ?string
    {
        if (!self::perm($reseller, 'can_regenerate_subscription')) {
            throw new \DomainException('شما اجازه بازتولید لینک اشتراک ندارید.');
        }
        $res = $this->rw->revokeSubscription((string) $config['remnawave_uuid']);
        $sub = $this->rw->subscriptionUrl($res);
        if (!$sub) {
            // Re-fetch the user to get the fresh URL.
            $sub = $this->rw->subscriptionUrl($this->rw->getUser((string) $config['remnawave_uuid']));
        }
        if ($sub) {
            Db::exec('UPDATE configs SET subscription_url = :s WHERE id = :id', [':s' => $sub, ':id' => $config['id']]);
        }
        AuditLogger::log('config.regenerate', 'config', (int) $config['id']);
        return $sub;
    }

    // ── Delete (with VOLUME-ONLY refund) ────────────────────────────

    public function delete(array $reseller, array $config): int
    {
        if (!self::perm($reseller, 'can_delete_config')) {
            throw new \DomainException('شما اجازه حذف کانفیگ ندارید.');
        }

        // Refresh usage from Remnawave so the refund is accurate.
        $usedBytes = (int) $config['last_used_bytes'];
        try {
            $usedBytes = $this->rw->getUserUsage((string) $config['remnawave_uuid']);
        } catch (RemnawaveException $e) {
            error_log('[delete] usage refresh failed: ' . $e->getMessage());
        }

        $refund = self::refundAmount((int) $config['volume_gb'], $usedBytes, (int) $config['price_charged']);

        try {
            $this->rw->deleteUser((string) $config['remnawave_uuid']);
        } catch (RemnawaveException $e) {
            // If already gone (404) we still mark deleted locally.
            if ($e->statusCode !== 404) {
                throw $e;
            }
        }

        Db::exec(
            'UPDATE configs SET status = "deleted", last_used_bytes = :u, last_synced_at = UTC_TIMESTAMP() WHERE id = :id',
            [':u' => $usedBytes, ':id' => $config['id']]
        );

        if ($refund > 0) {
            WalletService::refund((int) $reseller['id'], $refund, 'بازگشت وجه حذف کانفیگ ' . $config['remnawave_username'] . ' (حجم استفاده‌نشده)', (int) $config['id']);
        }
        AuditLogger::log('config.delete', 'config', (int) $config['id'], ['refund' => $refund, 'used_bytes' => $usedBytes]);
        return $refund;
    }

    /** refund = floor(price * unused_gb / volume_gb) ; volume only. */
    public static function refundAmount(int $volumeGb, int $usedBytes, int $priceCharged): int
    {
        if ($volumeGb <= 0 || $priceCharged <= 0) {
            return 0;
        }
        $usedGb = $usedBytes / 1073741824;
        $unusedGb = max(0.0, $volumeGb - $usedGb);
        return (int) floor($priceCharged * $unusedGb / $volumeGb);
    }

    // ── Usage sync (used by cron + on-demand) ───────────────────────

    public function syncUsage(array $config): void
    {
        try {
            $user = $this->rw->getUser((string) $config['remnawave_uuid']);
        } catch (RemnawaveException $e) {
            if ($e->statusCode === 404 && $config['status'] !== 'deleted') {
                Db::exec('UPDATE configs SET status = "deleted", last_synced_at = UTC_TIMESTAMP() WHERE id = :id', [':id' => $config['id']]);
            }
            return;
        }
        $used = $this->rw->usedBytes($user);
        $expired = $this->rw->userExpired($user);
        $status = $config['status'];
        if ($expired && $status === 'active') {
            $status = 'expired';
        }
        Db::exec(
            'UPDATE configs SET last_used_bytes = :u, status = :s, last_synced_at = UTC_TIMESTAMP() WHERE id = :id',
            [':u' => $used, ':s' => $status, ':id' => $config['id']]
        );
    }

    // ── Naming / tagging ────────────────────────────────────────────

    public static function tag(int $resellerId): string
    {
        return 'RSL_' . $resellerId; // Remnawave tags must match ^[A-Z0-9_]+$
    }

    private function makeUsername(string $prefix, ?string $custom = null): string
    {
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', $prefix) ?: 'rsl';

        // Custom suffix chosen by the reseller (if permitted). Sanitised to
        // [A-Za-z0-9], 2–24 chars; falls back to random if empty/taken.
        if ($custom !== null) {
            $custom = substr(preg_replace('/[^A-Za-z0-9]/', '', $custom) ?? '', 0, 24);
            if (strlen($custom) >= 2) {
                $base = $prefix . '_' . $custom;
                $username = $base;
                // Ensure local uniqueness; Remnawave also rejects duplicates.
                if (Db::scalar('SELECT id FROM configs WHERE remnawave_username = :u', [':u' => $username])) {
                    $username = $base . '_' . bin2hex(random_bytes(2));
                }
                return $username;
            }
        }

        return $prefix . '_' . bin2hex(random_bytes(4)); // <prefix>_<random8>
    }
}

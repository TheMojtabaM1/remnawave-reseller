<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

/**
 * All balance mutations go through here. Balances and transaction amounts
 * are signed integer Toman. Every mutation writes a `transactions` row with
 * the resulting balance_after, and charging triggers an auto-suspend check.
 */
final class WalletService
{
    /** Can this reseller afford a charge of $amount given debt rules? */
    public static function canAfford(array $reseller, int $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }
        $newBalance = (int) $reseller['balance'] - $amount;
        if ($newBalance >= 0) {
            return true;
        }
        if (!$reseller['allow_debt']) {
            return false;
        }
        if ($reseller['debt_limit'] === null) {
            return true; // unlimited debt
        }
        return $newBalance >= -((int) $reseller['debt_limit']);
    }

    public static function affordError(array $reseller, int $amount): string
    {
        $balance = (int) $reseller['balance'];
        if (!$reseller['allow_debt']) {
            return 'موجودی کیف پول کافی نیست (موجودی فعلی: ' . toman($balance) . ').';
        }
        return 'سقف بدهی مجاز شما تکمیل شده است (موجودی فعلی: ' . toman($balance) . ').';
    }

    /**
     * Charge a reseller (deduct). Returns new balance.
     * Throws \RuntimeException if not affordable (caller should pre-check).
     */
    public static function charge(int $resellerId, int $amount, string $description, ?int $configId = null): int
    {
        return Db::transaction(function () use ($resellerId, $amount, $description, $configId) {
            $r = self::lock($resellerId);
            if (!self::canAfford($r, $amount)) {
                throw new \RuntimeException(self::affordError($r, $amount));
            }
            $new = (int) $r['balance'] - $amount;
            self::apply($resellerId, $new, 'charge', -$amount, $description, $configId, null);
            self::maybeAutoSuspend($resellerId, $new, $r);
            return $new;
        });
    }

    /** Credit a refund (volume-only refunds, see ConfigService). */
    public static function refund(int $resellerId, int $amount, string $description, ?int $configId = null): int
    {
        return self::credit($resellerId, $amount, 'refund', $description, $configId, null);
    }

    /** Owner top-up / gift / manual adjustment. $type ∈ topup|gift|manual_adjust. */
    public static function credit(int $resellerId, int $amount, string $type, string $description, ?int $configId = null, ?int $adminId = null): int
    {
        return Db::transaction(function () use ($resellerId, $amount, $type, $description, $configId, $adminId) {
            $r = self::lock($resellerId);
            $new = (int) $r['balance'] + $amount;
            self::apply($resellerId, $new, $type, $amount, $description, $configId, $adminId);
            return $new;
        });
    }

    /** Owner sets balance to an absolute value. */
    public static function setBalance(int $resellerId, int $value, string $description, ?int $adminId = null): int
    {
        return Db::transaction(function () use ($resellerId, $value, $description, $adminId) {
            $r = self::lock($resellerId);
            $delta = $value - (int) $r['balance'];
            self::apply($resellerId, $value, 'manual_adjust', $delta, $description, null, $adminId);
            return $value;
        });
    }

    // ── internals ───────────────────────────────────────────────────

    private static function lock(int $resellerId): array
    {
        $r = Db::one('SELECT * FROM resellers WHERE id = :id FOR UPDATE', [':id' => $resellerId]);
        if (!$r) {
            throw new \RuntimeException('نماینده یافت نشد.');
        }
        return $r;
    }

    private static function apply(int $resellerId, int $newBalance, string $type, int $amount, string $desc, ?int $configId, ?int $adminId): void
    {
        Db::exec('UPDATE resellers SET balance = :b WHERE id = :id', [':b' => $newBalance, ':id' => $resellerId]);
        Db::exec(
            'INSERT INTO transactions (reseller_id, type, amount, balance_after, related_config_id, description, admin_id, created_at)
             VALUES (:rid, :t, :a, :ba, :cid, :d, :aid, UTC_TIMESTAMP())',
            [
                ':rid' => $resellerId, ':t' => $type, ':a' => $amount, ':ba' => $newBalance,
                ':cid' => $configId, ':d' => mb_substr($desc, 0, 255), ':aid' => $adminId,
            ]
        );
    }

    private static function maybeAutoSuspend(int $resellerId, int $newBalance, array $r): void
    {
        if (!$r['allow_debt'] || $r['debt_limit'] === null) {
            return;
        }
        if ($newBalance < -((int) $r['debt_limit']) && $r['status'] === 'active') {
            Db::exec('UPDATE resellers SET status = "suspended" WHERE id = :id', [':id' => $resellerId]);
            AlertService::raise(
                'reseller_suspended',
                'نماینده «' . ($r['display_name'] ?: $r['username']) . '» به دلیل عبور از سقف بدهی به‌صورت خودکار معلق شد.',
                'critical',
                'reseller:' . $resellerId
            );
            AuditLogger::log('reseller.auto_suspend', 'reseller', $resellerId, ['balance' => $newBalance], 'owner', 0);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Db;

/**
 * Computes the price (integer Toman) of a config.
 *
 *  - Plan config: uses the plan's fixed price (discount applied if set).
 *  - Custom config: per-GB (tiered) + per-day, then per-reseller discount.
 *
 * Per-GB rate resolution order:
 *   1. reseller.price_per_gb override
 *   2. matching price_tiers (plan scope first, then global), highest min_gb ≤ volume
 *   (custom configs have no plan, so only global tiers apply)
 */
final class PricingService
{
    /** Price for a plan-based config. */
    public static function planPrice(array $plan, array $reseller): int
    {
        $price = (int) $plan['price'];
        return self::applyDiscount($price, $reseller);
    }

    /** Price for a custom volume/duration config. */
    public static function customPrice(int $volumeGb, int $days, array $reseller, ?int $planId = null): int
    {
        $perGb  = self::perGbRate($volumeGb, $reseller, $planId);
        $perDay = $reseller['price_per_day'] !== null
            ? (int) $reseller['price_per_day']
            : (int) Config::get('default_price_per_day', 0);

        $raw = ($perGb * $volumeGb) + ($perDay * $days);
        return self::applyDiscount($raw, $reseller);
    }

    /** Resolve the effective per-GB rate for a given volume. */
    public static function perGbRate(int $volumeGb, array $reseller, ?int $planId = null): int
    {
        if ($reseller['price_per_gb'] !== null) {
            return (int) $reseller['price_per_gb'];
        }

        // Plan-scoped tiers (if a plan is involved), then global tiers.
        if ($planId !== null) {
            $rate = self::tierRate('plan', $planId, $volumeGb);
            if ($rate !== null) {
                return $rate;
            }
        }
        $rate = self::tierRate('global', null, $volumeGb);
        // Fall back to the global default per-GB price (owner Settings).
        return $rate ?? (int) Config::get('default_price_per_gb', 0);
    }

    private static function tierRate(string $scope, ?int $planId, int $volumeGb): ?int
    {
        $row = Db::one(
            'SELECT price_per_gb FROM price_tiers
             WHERE scope = :s AND plan_id <=> :p AND min_gb <= :v
             ORDER BY min_gb DESC LIMIT 1',
            [':s' => $scope, ':p' => $planId, ':v' => $volumeGb]
        );
        return $row ? (int) $row['price_per_gb'] : null;
    }

    private static function applyDiscount(int $price, array $reseller): int
    {
        $pct = $reseller['discount_percent'] !== null ? (int) $reseller['discount_percent'] : 0;
        if ($pct > 0) {
            $price = (int) floor($price * (100 - $pct) / 100);
        }
        return max(0, $price);
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Db;

final class AuditLogger
{
    public static function log(
        string $action,
        ?string $targetType = null,
        int|string|null $targetId = null,
        array $details = [],
        ?string $actorType = null,
        ?int $actorId = null
    ): void {
        $actorType ??= Auth::type() ?? 'system';
        $actorId   ??= Auth::id() ?? 0;
        $ip = self::ip();

        try {
            Db::exec(
                'INSERT INTO audit_logs (actor_type, actor_id, action, target_type, target_id, details, ip, created_at)
                 VALUES (:at, :ai, :ac, :tt, :ti, :d, :ip, UTC_TIMESTAMP())',
                [
                    ':at' => $actorType,
                    ':ai' => $actorId,
                    ':ac' => $action,
                    ':tt' => $targetType,
                    ':ti' => $targetId === null ? null : (string) $targetId,
                    ':d'  => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
                    ':ip' => $ip,
                ]
            );
        } catch (\Throwable $e) {
            error_log('[audit] failed: ' . $e->getMessage());
        }
    }

    private static function ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $h) {
            if (!empty($_SERVER[$h])) {
                return trim(explode(',', (string) $_SERVER[$h])[0]);
            }
        }
        return 'cli';
    }
}

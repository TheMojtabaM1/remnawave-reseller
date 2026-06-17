<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Db;

final class AlertService
{
    /** Raise an alert, de-duplicating identical unread alerts of the same type/ref. */
    public static function raise(string $type, string $message, string $severity = 'warning', ?string $targetRef = null): void
    {
        $dup = Db::one(
            'SELECT id FROM alerts WHERE type = :t AND target_ref <=> :r AND is_read = 0
             AND created_at > (UTC_TIMESTAMP() - INTERVAL 6 HOUR) LIMIT 1',
            [':t' => $type, ':r' => $targetRef]
        );
        if ($dup) {
            return;
        }
        Db::exec(
            'INSERT INTO alerts (type, severity, message, target_ref, is_read, created_at)
             VALUES (:t, :s, :m, :r, 0, UTC_TIMESTAMP())',
            [':t' => $type, ':s' => $severity, ':m' => mb_substr($message, 0, 500), ':r' => $targetRef]
        );
    }

    public static function unreadCount(): int
    {
        return (int) Db::scalar('SELECT COUNT(*) FROM alerts WHERE is_read = 0');
    }
}

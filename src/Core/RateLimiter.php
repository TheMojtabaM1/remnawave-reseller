<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple file-based fixed-window rate limiter (used for login throttling).
 */
final class RateLimiter
{
    private static function dir(): string
    {
        $dir = dirname(__DIR__, 2) . '/storage/logs/ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private static function file(string $key): string
    {
        return self::dir() . '/' . sha1($key) . '.json';
    }

    /** Returns true if the action is allowed (and records the hit). */
    public static function attempt(string $key, int $max = 5, int $windowSeconds = 300): bool
    {
        $file = self::file($key);
        $now = time();
        $data = ['count' => 0, 'reset' => $now + $windowSeconds];
        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded) && ($decoded['reset'] ?? 0) > $now) {
                $data = $decoded;
            }
        }
        $data['count']++;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return $data['count'] <= $max;
    }

    public static function secondsLeft(string $key): int
    {
        $file = self::file($key);
        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded)) {
                return max(0, (int) ($decoded['reset'] ?? 0) - time());
            }
        }
        return 0;
    }

    public static function clear(string $key): void
    {
        @unlink(self::file($key));
    }
}

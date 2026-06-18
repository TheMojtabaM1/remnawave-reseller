<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Csrf;

/**
 * Global helper functions (autoloaded via composer "files").
 */

if (!function_exists('e')) {
    /** HTML-escape for safe output. */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('old')) {
    /** Flash old form input back after a validation redirect. */
    function old(string $key, mixed $default = ''): string
    {
        $v = $_SESSION['_old'][$key] ?? $default;
        return e(is_array($v) ? '' : $v);
    }
}

if (!function_exists('flash')) {
    /** Set or read a one-shot flash message. */
    function flash(?string $type = null, ?string $message = null): array
    {
        if ($type !== null && $message !== null) {
            $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
            return [];
        }
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }
}

if (!function_exists('flash_old')) {
    function flash_old(array $input): void
    {
        unset($input['_csrf'], $input['password']);
        $_SESSION['_old'] = $input;
    }
}

if (!function_exists('clear_old')) {
    function clear_old(): void
    {
        unset($_SESSION['_old']);
    }
}

if (!function_exists('toman')) {
    /** Format an integer Toman amount with thousands separators. */
    function toman(int|float|null $amount): string
    {
        $n = (int) round((float) ($amount ?? 0));
        $sign = $n < 0 ? '-' : '';
        return $sign . number_format(abs($n)) . ' تومان';
    }
}

if (!function_exists('gb_to_bytes')) {
    function gb_to_bytes(int|float $gb): int
    {
        return (int) round($gb * 1073741824);
    }
}

if (!function_exists('bytes_to_gb')) {
    function bytes_to_gb(int|float $bytes): float
    {
        return $bytes / 1073741824;
    }
}

if (!function_exists('human_bytes')) {
    function human_bytes(int|float|null $bytes): string
    {
        $bytes = (float) ($bytes ?? 0);
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return number_format($bytes, $i === 0 ? 0 : 2) . ' ' . $units[$i];
    }
}

if (!function_exists('gregorian_to_jalali')) {
    /** Pure-PHP Gregorian → Jalali (Shamsi) date conversion. */
    function gregorian_to_jalali(int $gy, int $gm, int $gd): array
    {
        $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
            + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * intdiv($days, 12053));
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;
        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }
        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }
        return [$jy, $jm, $jd];
    }
}

if (!function_exists('shamsi')) {
    /**
     * Convert a UTC datetime string to Asia/Tehran Jalali display.
     * $format: 'datetime' | 'date' | 'time'
     */
    function shamsi(?string $utc, string $format = 'datetime'): string
    {
        if (empty($utc) || $utc === '0000-00-00 00:00:00') {
            return '—';
        }
        try {
            $dt = new DateTime($utc, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('Asia/Tehran'));
        } catch (\Throwable) {
            return e($utc);
        }
        [$jy, $jm, $jd] = gregorian_to_jalali(
            (int) $dt->format('Y'),
            (int) $dt->format('m'),
            (int) $dt->format('d')
        );
        $date = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);
        $time = $dt->format('H:i');
        return match ($format) {
            'date' => $date,
            'time' => $time,
            default => $date . ' ' . $time,
        };
    }
}

if (!function_exists('now_utc')) {
    function now_utc(): string
    {
        return (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}

if (!function_exists('iso8601_from_days')) {
    /** ISO-8601 timestamp $days from now, for Remnawave expireAt. */
    function iso8601_from_days(int $days): string
    {
        $dt = new DateTime('now', new DateTimeZone('UTC'));
        $dt->modify("+{$days} days");
        return $dt->format('Y-m-d\TH:i:s.000\Z');
    }
}

if (!function_exists('is_owner')) {
    function is_owner(): bool
    {
        return Auth::isOwner();
    }
}

if (!function_exists('config_value')) {
    function config_value(string $key, mixed $default = null): mixed
    {
        return App\Core\Config::get($key, $default);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect_back')) {
    function redirect_back(string $fallback = '/'): never
    {
        $to = $_SERVER['HTTP_REFERER'] ?? $fallback;
        App\Core\Response::redirect($to);
    }
}

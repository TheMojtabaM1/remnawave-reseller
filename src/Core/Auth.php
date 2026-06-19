<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session-based authentication for two actor types: owner (admins table)
 * and reseller (resellers table). Stores minimal identity in the session.
 */
final class Auth
{
    public static function bootSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $secure = (($_SERVER['HTTPS'] ?? '') === 'on')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $secure,
            'samesite' => 'Strict',
        ]);
        // Per-port session name so the owner/reseller demo portals (different
        // ports, same host) don't share one login. In production (single
        // domain) this is one site with role-based routing.
        session_name('rwr_' . (int) ($_SERVER['SERVER_PORT'] ?? 0));
        session_start();
    }

    public static function login(string $type, array $identity): void
    {
        session_regenerate_id(true);
        $_SESSION['actor_type'] = $type;          // 'owner' | 'reseller'
        $_SESSION['actor_id']   = (int) $identity['id'];
        $_SESSION['actor_name'] = $identity['username'] ?? '';
        $_SESSION['logged_in_at'] = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['actor_id']) && !empty($_SESSION['actor_type']);
    }

    public static function type(): ?string
    {
        return $_SESSION['actor_type'] ?? null;
    }

    public static function id(): ?int
    {
        return isset($_SESSION['actor_id']) ? (int) $_SESSION['actor_id'] : null;
    }

    public static function name(): string
    {
        return (string) ($_SESSION['actor_name'] ?? '');
    }

    public static function isOwner(): bool
    {
        return self::type() === 'owner';
    }

    public static function isReseller(): bool
    {
        return self::type() === 'reseller';
    }

    /** Currently-authenticated reseller row, refreshed from DB (for live limits/balance). */
    public static function reseller(): ?array
    {
        if (!self::isReseller()) {
            return null;
        }
        return Db::one('SELECT * FROM resellers WHERE id = :id', [':id' => self::id()]);
    }

    // ── Middleware helpers ───────────────────────────────────────────
    public static function guardOwner(): void
    {
        if (!self::isOwner()) {
            Response::redirect('/login');
        }
    }

    public static function guardReseller(): void
    {
        if (!self::isReseller()) {
            Response::redirect('/login');
        }
        $r = self::reseller();
        if (!$r || $r['status'] !== 'active') {
            self::logout();
            Response::abort(403, 'حساب نمایندگی شما غیرفعال یا معلق است. با مدیر تماس بگیرید.');
        }
        if (!empty($r['access_expires_at']) && strtotime((string) $r['access_expires_at']) < time()) {
            self::logout();
            Response::abort(403, 'مدت دسترسی حساب نمایندگی شما به پایان رسیده است.');
        }
    }
}

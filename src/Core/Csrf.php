<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    /** Hidden input markup for forms. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function check(Request $request): void
    {
        if (!$request->isPost()) {
            return;
        }
        $sent = (string) $request->post('_csrf', '');
        $real = $_SESSION[self::KEY] ?? '';
        if ($real === '' || !hash_equals($real, $sent)) {
            Response::abort(419, 'توکن امنیتی نامعتبر است. صفحه را تازه کنید و دوباره تلاش کنید.');
        }
    }
}

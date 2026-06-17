<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Db;
use App\Core\RateLimiter;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditLogger;

final class LoginController
{
    public function show(): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }
        View::render('auth/login', ['title' => 'ورود'], 'auth');
    }

    public function submit(Request $request): void
    {
        $username = trim((string) $request->post('username', ''));
        $password = (string) $request->post('password', '');

        $key = 'login:' . $request->ip() . ':' . strtolower($username);
        if (!RateLimiter::attempt($key, 6, 600)) {
            $secs = RateLimiter::secondsLeft($key);
            flash('error', 'تلاش‌های ناموفق زیاد. لطفاً ' . ceil($secs / 60) . ' دقیقه دیگر تلاش کنید.');
            flash_old($request->all());
            Response::redirect('/login');
        }

        // Owner first, then reseller.
        $admin = Db::one('SELECT * FROM admins WHERE username = :u AND status = "active"', [':u' => $username]);
        if ($admin && password_verify($password, $admin['password_hash'])) {
            RateLimiter::clear($key);
            Auth::login('owner', $admin);
            AuditLogger::log('auth.login', 'admin', (int) $admin['id'], ['role' => 'owner']);
            clear_old();
            Response::redirect('/owner');
        }

        $reseller = Db::one('SELECT * FROM resellers WHERE username = :u', [':u' => $username]);
        if ($reseller && password_verify($password, $reseller['password_hash'])) {
            if ($reseller['status'] !== 'active') {
                flash('error', 'حساب نمایندگی شما معلق است. با مدیر تماس بگیرید.');
                Response::redirect('/login');
            }
            RateLimiter::clear($key);
            Auth::login('reseller', $reseller);
            AuditLogger::log('auth.login', 'reseller', (int) $reseller['id'], ['role' => 'reseller']);
            clear_old();
            Response::redirect('/panel');
        }

        flash('error', 'نام کاربری یا رمز عبور نادرست است.');
        flash_old($request->all());
        Response::redirect('/login');
    }
}

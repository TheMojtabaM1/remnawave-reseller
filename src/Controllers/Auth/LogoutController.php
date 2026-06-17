<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Core\Auth;
use App\Core\Response;
use App\Services\AuditLogger;

final class LogoutController
{
    public function logout(): void
    {
        if (Auth::check()) {
            AuditLogger::log('auth.logout', Auth::type(), Auth::id());
        }
        Auth::logout();
        Response::redirect('/login');
    }
}

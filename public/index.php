<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/vendor/autoload.php';

// ── Load environment ────────────────────────────────────────────────
if (is_file(APP_ROOT . '/.env')) {
    Dotenv\Dotenv::createImmutable(APP_ROOT)->safeLoad();
}

date_default_timezone_set('UTC'); // store UTC; display converts to Tehran

// ── Error handling ──────────────────────────────────────────────────
$debug = (bool) Config::env('APP_DEBUG', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/storage/logs/php-error.log');

set_exception_handler(function (\Throwable $e) use ($debug) {
    error_log('[uncaught] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if ($debug) {
        Response::abort(500, $e->getMessage() . "\n" . $e->getTraceAsString());
    }
    Response::abort(500, 'خطای غیرمنتظره‌ای رخ داد. لطفاً بعداً تلاش کنید.');
});

// ── Session ─────────────────────────────────────────────────────────
Auth::bootSession();

// ── Route & dispatch ────────────────────────────────────────────────
$router = new Router();
require APP_ROOT . '/src/routes.php';

$request = new Request();
$router->dispatch($request);

<?php

declare(strict_types=1);

/**
 * Route table. $router is provided by the front controller.
 *
 * Middleware are plain callables run before the handler:
 *   $csrf  — verify CSRF token on POST
 *   $owner — require an authenticated owner
 *   $rsl   — require an active authenticated reseller
 */

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;

/** @var App\Core\Router $router */

$csrf  = fn(Request $r) => Csrf::check($r);
$owner = fn(Request $r) => Auth::guardOwner();
$rsl   = fn(Request $r) => Auth::guardReseller();

// ── Root ────────────────────────────────────────────────────────────
$router->get('/', function () {
    if (Auth::isOwner()) {
        Response::redirect('/owner');
    }
    if (Auth::isReseller()) {
        Response::redirect('/panel');
    }
    Response::redirect('/login');
});

// ── Auth ────────────────────────────────────────────────────────────
$router->get('/login', [App\Controllers\Auth\LoginController::class, 'show']);
$router->post('/login', [App\Controllers\Auth\LoginController::class, 'submit'], [$csrf]);
$router->post('/logout', [App\Controllers\Auth\LogoutController::class, 'logout'], [$csrf]);

// ════════════════════════════════════════════════════════════════════
//  OWNER (super-admin)
// ════════════════════════════════════════════════════════════════════
$O = 'App\\Controllers\\Owner\\';

$router->get('/owner', [$O . 'DashboardController', 'index'], [$owner]);

// Resellers
$router->get('/owner/resellers', [$O . 'ResellerController', 'index'], [$owner]);
$router->get('/owner/resellers/create', [$O . 'ResellerController', 'create'], [$owner]);
$router->post('/owner/resellers', [$O . 'ResellerController', 'store'], [$owner, $csrf]);
$router->get('/owner/resellers/{id}', [$O . 'ResellerController', 'show'], [$owner]);
$router->get('/owner/resellers/{id}/edit', [$O . 'ResellerController', 'edit'], [$owner]);
$router->post('/owner/resellers/{id}', [$O . 'ResellerController', 'update'], [$owner, $csrf]);
$router->post('/owner/resellers/{id}/status', [$O . 'ResellerController', 'toggleStatus'], [$owner, $csrf]);
$router->post('/owner/resellers/{id}/delete', [$O . 'ResellerController', 'destroy'], [$owner, $csrf]);

// Wallet
$router->post('/owner/resellers/{id}/wallet', [$O . 'WalletController', 'adjust'], [$owner, $csrf]);

// Plans
$router->get('/owner/plans', [$O . 'PlanController', 'index'], [$owner]);
$router->post('/owner/plans', [$O . 'PlanController', 'store'], [$owner, $csrf]);
$router->post('/owner/plans/{id}', [$O . 'PlanController', 'update'], [$owner, $csrf]);
$router->post('/owner/plans/{id}/delete', [$O . 'PlanController', 'destroy'], [$owner, $csrf]);

// Templates
$router->get('/owner/templates', [$O . 'TemplateController', 'index'], [$owner]);
$router->post('/owner/templates', [$O . 'TemplateController', 'store'], [$owner, $csrf]);
$router->post('/owner/templates/{id}', [$O . 'TemplateController', 'update'], [$owner, $csrf]);
$router->post('/owner/templates/{id}/delete', [$O . 'TemplateController', 'destroy'], [$owner, $csrf]);

// Price tiers
$router->get('/owner/pricing', [$O . 'PriceTierController', 'index'], [$owner]);
$router->post('/owner/pricing', [$O . 'PriceTierController', 'store'], [$owner, $csrf]);
$router->post('/owner/pricing/{id}/delete', [$O . 'PriceTierController', 'destroy'], [$owner, $csrf]);

// Reports
$router->get('/owner/reports', [$O . 'ReportController', 'index'], [$owner]);
$router->get('/owner/reports/export', [$O . 'ReportController', 'exportExcel'], [$owner]);
$router->get('/owner/reports/series', [$O . 'ReportController', 'series'], [$owner]);

// Bulk operations
$router->get('/owner/bulk', [$O . 'BulkController', 'index'], [$owner]);
$router->post('/owner/bulk/action', [$O . 'BulkController', 'action'], [$owner, $csrf]);
$router->post('/owner/bulk/create', [$O . 'BulkController', 'bulkCreate'], [$owner, $csrf]);

// Audit, alerts, monitors
$router->get('/owner/audit', [$O . 'AuditController', 'index'], [$owner]);
$router->get('/owner/alerts', [$O . 'AlertController', 'index'], [$owner]);
$router->post('/owner/alerts/{id}/read', [$O . 'AlertController', 'markRead'], [$owner, $csrf]);
$router->post('/owner/alerts/read-all', [$O . 'AlertController', 'markAllRead'], [$owner, $csrf]);
$router->get('/owner/monitor', [$O . 'MonitorController', 'index'], [$owner]);
$router->get('/owner/nodes', [$O . 'NodeController', 'index'], [$owner]);
$router->get('/owner/leaderboard', [$O . 'LeaderboardController', 'index'], [$owner]);

// Statements
$router->get('/owner/statements', [$O . 'StatementController', 'index'], [$owner]);
$router->post('/owner/statements/generate', [$O . 'StatementController', 'generate'], [$owner, $csrf]);
$router->get('/owner/statements/{id}/download', [$O . 'StatementController', 'download'], [$owner]);

// Backups
$router->get('/owner/backups', [$O . 'BackupController', 'index'], [$owner]);
$router->post('/owner/backups/create', [$O . 'BackupController', 'create'], [$owner, $csrf]);
$router->get('/owner/backups/download', [$O . 'BackupController', 'download'], [$owner]);
$router->post('/owner/backups/restore', [$O . 'BackupController', 'restore'], [$owner, $csrf]);

// Settings
$router->get('/owner/settings', [$O . 'SettingsController', 'index'], [$owner]);
$router->post('/owner/settings', [$O . 'SettingsController', 'update'], [$owner, $csrf]);

// ════════════════════════════════════════════════════════════════════
//  RESELLER (agent dashboard)
// ════════════════════════════════════════════════════════════════════
$R = 'App\\Controllers\\Reseller\\';

$router->get('/panel', [$R . 'DashboardController', 'index'], [$rsl]);

// Configs
$router->get('/panel/configs', [$R . 'ConfigController', 'index'], [$rsl]);
$router->get('/panel/configs/create', [$R . 'ConfigController', 'create'], [$rsl]);
$router->post('/panel/configs', [$R . 'ConfigController', 'store'], [$rsl, $csrf]);
$router->get('/panel/configs/{id}', [$R . 'ConfigController', 'show'], [$rsl]);
$router->post('/panel/configs/{id}/renew', [$R . 'ConfigController', 'renew'], [$rsl, $csrf]);
$router->post('/panel/configs/{id}/toggle', [$R . 'ConfigController', 'toggle'], [$rsl, $csrf]);
$router->post('/panel/configs/{id}/regenerate', [$R . 'ConfigController', 'regenerate'], [$rsl, $csrf]);
$router->post('/panel/configs/{id}/delete', [$R . 'ConfigController', 'destroy'], [$rsl, $csrf]);
$router->get('/panel/configs/{id}/qr', [$R . 'ConfigController', 'qr'], [$rsl]);

// Reseller bulk on own configs
$router->post('/panel/configs/bulk', [$R . 'ConfigController', 'bulk'], [$rsl, $csrf]);

// Wallet
$router->get('/panel/wallet', [$R . 'WalletController', 'index'], [$rsl]);

<?php

declare(strict_types=1);

/**
 * Seeder — creates the owner account and default settings.
 * Usage:
 *   php database/seed.php <owner_username> <owner_password>
 * Re-runnable: updates the owner password if the username already exists.
 */

use App\Core\Db;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';

if (is_file(APP_ROOT . '/.env')) {
    Dotenv\Dotenv::createImmutable(APP_ROOT)->safeLoad();
}

$username = $argv[1] ?? getenv('OWNER_USERNAME') ?: 'admin';
$password = $argv[2] ?? getenv('OWNER_PASSWORD') ?: '';

if ($password === '') {
    fwrite(STDERR, "Owner password required: php database/seed.php <username> <password>\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_ARGON2ID);
$now  = (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');

$existing = Db::one('SELECT id FROM admins WHERE username = :u', [':u' => $username]);
if ($existing) {
    Db::exec('UPDATE admins SET password_hash = :h, status = "active" WHERE id = :id', [
        ':h' => $hash, ':id' => $existing['id'],
    ]);
    echo "Owner '{$username}' updated.\n";
} else {
    Db::exec(
        'INSERT INTO admins (username, password_hash, role, status, created_at)
         VALUES (:u, :h, "owner", "active", :c)',
        [':u' => $username, ':h' => $hash, ':c' => $now]
    );
    echo "Owner '{$username}' created.\n";
}

// Default settings (only insert if missing).
$defaults = [
    'app_name'                 => getenv('APP_NAME') ?: 'USVSIR Panel',
    'cleanup_grace_days'       => getenv('CLEANUP_GRACE_DAYS') ?: '3',
    'low_balance_threshold'    => getenv('LOW_BALANCE_THRESHOLD') ?: '50000',
    'default_traffic_strategy' => getenv('DEFAULT_TRAFFIC_STRATEGY') ?: 'NO_RESET',
    'traffic_spike_gb'         => '50',
];
foreach ($defaults as $k => $v) {
    Db::exec(
        'INSERT INTO settings (`key`, `value`) VALUES (:k, :v)
         ON DUPLICATE KEY UPDATE `value` = `value`',
        [':k' => $k, ':v' => (string) $v]
    );
}
echo "Default settings ensured.\n";

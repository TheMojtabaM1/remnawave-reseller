<?php

declare(strict_types=1);

/**
 * Idempotent migration runner.
 *   php database/migrate.php
 * Applies every migrations/*.sql not yet recorded in the `migrations` table.
 */

use App\Core\Db;

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';

if (is_file(APP_ROOT . '/.env')) {
    Dotenv\Dotenv::createImmutable(APP_ROOT)->safeLoad();
}

$pdo = Db::pdo();

// Ensure tracking table exists first.
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(191) NOT NULL UNIQUE,
        applied_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = array_column(
    $pdo->query('SELECT filename FROM migrations')->fetchAll(),
    'filename'
);

$files = glob(APP_ROOT . '/migrations/*.sql') ?: [];
sort($files);

$count = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (in_array($name, $applied, true)) {
        continue;
    }
    echo "Applying {$name} ... ";
    $sql = (string) file_get_contents($file);

    // Split on ";" that terminate a statement (end of line / file).
    $statements = preg_split('/;\s*(\n|$)/', $sql) ?: [];
    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '' || str_starts_with($stmt, '--')) {
            continue;
        }
        $pdo->exec($stmt);
    }

    $ins = $pdo->prepare('INSERT INTO migrations (filename, applied_at) VALUES (?, UTC_TIMESTAMP())');
    $ins->execute([$name]);
    echo "done\n";
    $count++;
}

echo $count === 0 ? "Nothing to migrate. Database up to date.\n" : "Applied {$count} migration(s).\n";

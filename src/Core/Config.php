<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin wrapper around environment + the `settings` DB table.
 * Env values are loaded by phpdotenv in the front controller; DB settings
 * are lazy-loaded the first time get() is asked for a non-env key.
 */
final class Config
{
    private static array $settings = [];
    private static bool $settingsLoaded = false;

    /** Read an environment value (.env or real env). */
    public static function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => $value,
        };
    }

    /** Read a value from the `settings` table, falling back to env / default. */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$settingsLoaded) {
            self::loadSettings();
        }
        return self::$settings[$key] ?? self::env(strtoupper($key), $default);
    }

    public static function set(string $key, mixed $value): void
    {
        $pdo = Db::pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO settings (`key`, `value`) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE `value` = :v2'
        );
        $stmt->execute([':k' => $key, ':v' => (string) $value, ':v2' => (string) $value]);
        self::$settings[$key] = (string) $value;
    }

    private static function loadSettings(): void
    {
        self::$settingsLoaded = true;
        try {
            $rows = Db::pdo()->query('SELECT `key`, `value` FROM settings')->fetchAll();
            foreach ($rows as $row) {
                self::$settings[$row['key']] = $row['value'];
            }
        } catch (\Throwable) {
            // settings table not migrated yet — ignore, env still works.
        }
    }
}

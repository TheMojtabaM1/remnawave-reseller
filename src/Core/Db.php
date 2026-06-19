<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Single shared PDO connection (MySQL/MariaDB) using prepared statements.
 */
final class Db
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string) Config::env('DB_HOST', '127.0.0.1');
        $port = (string) Config::env('DB_PORT', '3306');
        $name = (string) Config::env('DB_NAME', 'remnawave_reseller');
        $user = (string) Config::env('DB_USER', 'remnawave_reseller');
        $pass = (string) Config::env('DB_PASS', '');

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        self::$pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00', NAMES utf8mb4",
        ]);

        return self::$pdo;
    }

    /** Prepare + execute, logging the SQL on failure for diagnostics. */
    private static function run(string $sql, array $params): \PDOStatement
    {
        try {
            $stmt = self::pdo()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            error_log('[db] ' . $e->getMessage() . ' | SQL: ' . preg_replace('/\s+/', ' ', $sql)
                . ' | params: ' . implode(',', array_keys($params)));
            throw $e;
        }
    }

    /** Run a query with bound params, return all rows. */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** Run a query with bound params, return first row or null. */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** Run a single scalar query. */
    public static function scalar(string $sql, array $params = []): mixed
    {
        return self::run($sql, $params)->fetchColumn();
    }

    /** Execute an INSERT/UPDATE/DELETE, return affected-row count. */
    public static function exec(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::exec($sql, $params);
        return (int) self::pdo()->lastInsertId();
    }

    public static function transaction(callable $fn): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $fn($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;

/**
 * Database backups via mysqldump, with rotation. Backups live in /backups
 * (gitignored). Restore reads a .sql or .sql.gz dump.
 */
final class BackupService
{
    public static function dir(): string
    {
        $dir = dirname(__DIR__, 2) . '/backups';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /** Create a gzipped dump, rotate old ones, return the file path. */
    public static function create(): string
    {
        $host = (string) Config::env('DB_HOST', '127.0.0.1');
        $port = (string) Config::env('DB_PORT', '3306');
        $name = (string) Config::env('DB_NAME', 'usvsir');
        $user = (string) Config::env('DB_USER', 'usvsir');
        $pass = (string) Config::env('DB_PASS', '');

        $file = self::dir() . '/usvsir_' . gmdate('Y-m-d_His') . '.sql.gz';

        $cmd = sprintf(
            'mysqldump --single-transaction --quick --no-tablespaces -h%s -P%s -u%s %s %s 2>/dev/null | gzip > %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            $pass !== '' ? '-p' . escapeshellarg($pass) : '',
            escapeshellarg($name),
            escapeshellarg($file)
        );
        exec($cmd, $out, $code);
        if ($code !== 0 || !is_file($file) || filesize($file) === 0) {
            @unlink($file);
            throw new \RuntimeException('تهیه پشتیبان ناموفق بود (mysqldump). لاگ را بررسی کنید.');
        }

        self::rotate();
        return $file;
    }

    /** Keep only the most recent BACKUP_KEEP files. */
    public static function rotate(): void
    {
        $keep = (int) Config::env('BACKUP_KEEP', 14);
        $files = glob(self::dir() . '/usvsir_*.sql.gz') ?: [];
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
    }

    public static function latest(): ?string
    {
        $files = glob(self::dir() . '/usvsir_*.sql.gz') ?: [];
        if (!$files) {
            return null;
        }
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        return $files[0];
    }

    public static function list(): array
    {
        $files = glob(self::dir() . '/usvsir_*.sql.gz') ?: [];
        usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
        return array_map(fn($f) => [
            'name' => basename($f),
            'size' => filesize($f),
            'mtime' => gmdate('Y-m-d H:i:s', filemtime($f)),
        ], $files);
    }

    /** Restore from an uploaded dump (.sql or .sql.gz). */
    public static function restore(string $path): void
    {
        $host = (string) Config::env('DB_HOST', '127.0.0.1');
        $port = (string) Config::env('DB_PORT', '3306');
        $name = (string) Config::env('DB_NAME', 'usvsir');
        $user = (string) Config::env('DB_USER', 'usvsir');
        $pass = (string) Config::env('DB_PASS', '');

        $reader = str_ends_with($path, '.gz') ? 'gunzip -c ' . escapeshellarg($path) : 'cat ' . escapeshellarg($path);
        $cmd = sprintf(
            '%s | mysql -h%s -P%s -u%s %s %s 2>/dev/null',
            $reader,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            $pass !== '' ? '-p' . escapeshellarg($pass) : '',
            escapeshellarg($name)
        );
        exec($cmd, $out, $code);
        if ($code !== 0) {
            throw new \RuntimeException('بازیابی پشتیبان ناموفق بود.');
        }
    }
}

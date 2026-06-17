<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $to): never
    {
        header('Location: ' . $to, true, 302);
        exit;
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        $titles = [403 => 'دسترسی غیرمجاز', 404 => 'یافت نشد', 419 => 'نشست منقضی شده', 500 => 'خطای داخلی'];
        $title = $titles[$status] ?? 'خطا';
        echo "<!doctype html><html lang='fa' dir='rtl'><head><meta charset='utf-8'>"
            . "<meta name='viewport' content='width=device-width,initial-scale=1'>"
            . "<title>{$status}</title>"
            . "<style>body{font-family:system-ui,Tahoma,sans-serif;background:#0f172a;color:#e2e8f0;"
            . "display:flex;min-height:100vh;align-items:center;justify-content:center;text-align:center}"
            . "h1{font-size:3rem;margin:0}p{color:#94a3b8}</style></head><body><div>"
            . "<h1>{$status}</h1><p>{$title}</p><p>" . htmlspecialchars($message) . "</p>"
            . "<p><a style='color:#60a5fa' href='/'>بازگشت</a></p></div></body></html>";
        exit;
    }

    /** Send a file as a download then exit. */
    public static function download(string $path, string $filename, string $mime = 'application/octet-stream'): never
    {
        if (!is_file($path)) {
            self::abort(404, 'فایل یافت نشد');
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) filesize($path));
        readfile($path);
        exit;
    }
}

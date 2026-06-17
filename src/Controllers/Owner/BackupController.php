<?php

declare(strict_types=1);

namespace App\Controllers\Owner;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\AuditLogger;
use App\Services\BackupService;

final class BackupController
{
    public function index(): void
    {
        View::render('owner/backups', ['title' => 'پشتیبان‌گیری', 'backups' => BackupService::list()]);
    }

    public function create(): void
    {
        try {
            $file = BackupService::create();
            AuditLogger::log('backup.create', 'file', basename($file));
            flash('success', 'پشتیبان تهیه شد: ' . basename($file));
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        Response::redirect('/owner/backups');
    }

    public function download(Request $request): void
    {
        $name = basename((string) $request->get('file', ''));
        $path = BackupService::dir() . '/' . $name;
        if ($name === '' || !is_file($path) || !str_starts_with($name, 'usvsir_')) {
            $latest = BackupService::latest();
            if (!$latest) {
                Response::abort(404, 'پشتیبانی موجود نیست');
            }
            $path = $latest;
            $name = basename($latest);
        }
        Response::download($path, $name, 'application/gzip');
    }

    public function restore(Request $request): void
    {
        if (empty($_FILES['backup']['tmp_name']) || !is_uploaded_file($_FILES['backup']['tmp_name'])) {
            flash('error', 'فایلی برای بازیابی انتخاب نشده است.');
            Response::redirect('/owner/backups');
        }
        $orig = (string) $_FILES['backup']['name'];
        if (!preg_match('/\.sql(\.gz)?$/', $orig)) {
            flash('error', 'فقط فایل‌های .sql یا .sql.gz پذیرفته می‌شوند.');
            Response::redirect('/owner/backups');
        }
        $tmp = $_FILES['backup']['tmp_name'];
        try {
            BackupService::restore($tmp);
            AuditLogger::log('backup.restore', 'file', $orig);
            flash('success', 'بازیابی با موفقیت انجام شد.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }
        Response::redirect('/owner/backups');
    }
}

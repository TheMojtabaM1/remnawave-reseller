<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use GuzzleHttp\Client;

/**
 * In-panel AI assistant (#118). Calls the Claude API if ANTHROPIC_API_KEY is
 * set in .env; otherwise returns a friendly "not configured" message.
 * Shared by owner and reseller (any authenticated user).
 */
final class AssistantController
{
    private const MODEL = 'claude-haiku-4-5-20251001';

    public function chat(Request $request): void
    {
        if (!Auth::check()) {
            Response::json(['reply' => 'لطفاً ابتدا وارد شوید.'], 401);
        }

        $message = trim((string) $request->post('message', ''));
        if ($message === '') {
            Response::json(['reply' => 'پیامی وارد نشده است.']);
        }
        $message = mb_substr($message, 0, 2000);

        $key = (string) Config::env('ANTHROPIC_API_KEY', '');
        if ($key === '') {
            Response::json(['reply' => 'دستیار هوش مصنوعی هنوز فعال نشده است. مدیر باید کلید ANTHROPIC_API_KEY را در فایل .env تنظیم کند.']);
        }

        $role = Auth::isOwner() ? 'مدیر (owner)' : 'نماینده (reseller)';
        $system = "تو دستیار فارسی‌زبان یک پنل مدیریت نمایندگانِ فروش VPN مبتنی بر Remnawave هستی. "
            . "کاربر فعلی نقش «{$role}» دارد. کوتاه، دقیق و فارسی پاسخ بده. "
            . "در مورد راهنمای اتصال اپ‌ها (v2rayNG، Hiddify، Happ)، رفع اشکال کانفیگ، و نحوه‌ی کار با پنل کمک کن. "
            . "هرگز اطلاعات حساس یا توکن نمایش نده.";

        try {
            $client = new Client(['timeout' => 45, 'http_errors' => false]);
            $resp = $client->post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $key,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'model' => self::MODEL,
                    'max_tokens' => 700,
                    'system' => $system,
                    'messages' => [['role' => 'user', 'content' => $message]],
                ],
            ]);
            $raw = (string) $resp->getBody();
            if ($resp->getStatusCode() >= 400) {
                error_log('[assistant] ' . $resp->getStatusCode() . ' ' . substr($raw, 0, 300));
                Response::json(['reply' => 'خطا در ارتباط با سرویس هوش مصنوعی. بعداً تلاش کنید.']);
            }
            $data = json_decode($raw, true);
            $text = $data['content'][0]['text'] ?? 'پاسخی دریافت نشد.';
            Response::json(['reply' => $text]);
        } catch (\Throwable $e) {
            error_log('[assistant] ' . $e->getMessage());
            Response::json(['reply' => 'خطای غیرمنتظره در دستیار.']);
        }
    }
}

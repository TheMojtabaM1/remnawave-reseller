<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Server-rendered PHP templates. A view renders into $content which is then
 * injected into a layout. Views live in /views, layouts in /views/layouts.
 */
final class View
{
    private static string $base = '';

    private static function base(): string
    {
        if (self::$base === '') {
            self::$base = dirname(__DIR__, 2) . '/views';
        }
        return self::$base;
    }

    /**
     * Render $template with $data inside $layout, then echo it.
     */
    public static function render(string $template, array $data = [], string $layout = 'owner'): void
    {
        $content = self::partial($template, $data);
        $layoutData = array_merge($data, ['content' => $content]);
        echo self::partial('layouts/' . $layout, $layoutData);
    }

    /** Render a template to a string without a layout. */
    public static function partial(string $template, array $data = []): string
    {
        $file = self::base() . '/' . $template . '.php';
        if (!is_file($file)) {
            Response::abort(500, "قالب یافت نشد: {$template}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }
}

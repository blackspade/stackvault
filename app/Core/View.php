<?php
declare(strict_types=1);

namespace App\Core;

class View
{
    /** Data shared across all views (e.g. current user, app name, current path). */
    private static array $shared = [];

    // ─── Sharing ──────────────────────────────────────────────────────────────

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function shareMany(array $data): void
    {
        self::$shared = array_merge(self::$shared, $data);
    }

    // ─── Rendering ────────────────────────────────────────────────────────────

    /**
     * Render a view template, optionally wrapped in a layout.
     *
     * @param string $template  Relative to resources/views/ (no .php extension)
     * @param array  $data      Variables made available to the template
     * @param string $layout    Relative to resources/layouts/ (no .php, empty = no layout)
     */
    public static function render(string $template, array $data = [], string $layout = ''): void
    {
        $templatePath = SV_ROOT . '/resources/views/' . $template . '.php';

        if (!file_exists($templatePath)) {
            throw new \RuntimeException("View not found: resources/views/{$template}.php");
        }

        // Merge shared data (data wins over shared on key conflict)
        $merged = array_merge(self::$shared, $data);

        // Always make app name available
        $merged['appName'] = $merged['appName'] ?? Config::get('APP_NAME', 'StackVault');

        // Render template into buffer
        $content = self::renderFile($templatePath, $merged);

        // Wrap in layout or output directly
        if ($layout !== '') {
            $layoutPath = SV_ROOT . '/resources/layouts/' . $layout . '.php';
            if (!file_exists($layoutPath)) {
                throw new \RuntimeException("Layout not found: resources/layouts/{$layout}.php");
            }
            // Re-merge shared data — picks up any View::share() calls made during
            // view rendering (e.g. $pageActions injected from a view template).
            $merged = array_merge($merged, self::$shared);
            $merged['content'] = $content;
            self::outputFile($layoutPath, $merged);
        } else {
            echo $content;
        }
    }

    /**
     * Render a component/partial and output it directly.
     */
    public static function partial(string $component, array $data = []): void
    {
        $path = SV_ROOT . '/resources/components/' . $component . '.php';
        if (file_exists($path)) {
            $merged = array_merge(self::$shared, $data);
            self::outputFile($path, $merged);
        }
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    /** Render a PHP file to a string. */
    private static function renderFile(string $path, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        return (string) ob_get_clean();
    }

    /** Render a PHP file and echo it directly. */
    private static function outputFile(string $path, array $data): void
    {
        extract($data, EXTR_SKIP);
        require $path;
    }
}

<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    /**
     * Redirect to a path (relative, prefixed with APP_URL) or absolute URL.
     */
    public static function redirect(string $path, int $code = 302): never
    {
        $url = str_starts_with($path, 'http') ? $path : url($path);
        header('Location: ' . $url, true, $code);
        exit;
    }

    /**
     * Output JSON and exit.
     */
    public static function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Render an error page and exit.
     */
    public static function abort(int $code, string $message = ''): never
    {
        http_response_code($code);

        $errorView = SV_ROOT . '/resources/views/errors/' . $code . '.php';
        if (file_exists($errorView)) {
            extract(['code' => $code, 'message' => $message]);
            require $errorView;
        } else {
            echo '<!doctype html><html><body>';
            echo '<h1>Error ' . $code . '</h1>';
            echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
            echo '</body></html>';
        }
        exit;
    }

    public static function notFound(string $message = 'Page not found.'): never
    {
        self::abort(404, $message);
    }

    public static function forbidden(string $message = 'Forbidden.'): never
    {
        self::abort(403, $message);
    }
}

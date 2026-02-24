<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $path;
    private array  $routeParams = [];

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path   = $this->resolvePath();
    }

    // ─── Path resolution ──────────────────────────────────────────────────────

    private function resolvePath(): string
    {
        $uri = rawurldecode($_SERVER['REQUEST_URI'] ?? '/');
        $uri = strtok($uri, '?') ?: '/'; // Strip query string

        // Strip APP_BASE_PATH (e.g. /stackvault)
        $base = rtrim(Config::get('APP_BASE_PATH', ''), '/');
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        // Strip /public prefix when accessed directly without root .htaccess
        if (str_starts_with($uri, '/public')) {
            $uri = substr($uri, strlen('/public'));
        }

        return $uri ?: '/';
    }

    // ─── HTTP method ──────────────────────────────────────────────────────────

    public function method(): string
    {
        return $this->method;
    }

    public function isGet(): bool
    {
        return $this->method === 'GET';
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    // ─── Path ─────────────────────────────────────────────────────────────────

    public function path(): string
    {
        return $this->path;
    }

    // ─── Input ────────────────────────────────────────────────────────────────

    /** Read from POST, then GET. */
    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    // ─── Route params (set by router after matching) ──────────────────────────

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function params(): array
    {
        return $this->routeParams;
    }

    // ─── Client info ──────────────────────────────────────────────────────────

    public function ip(): string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Only trust proxy headers if the connection comes from localhost/private network
        $trustedProxy = in_array($remoteAddr, ['127.0.0.1', '::1'], true)
            || (bool) preg_match('/^(10\.|172\.(1[6-9]|2\d|3[01])\.|192\.168\.)/', $remoteAddr);

        if ($trustedProxy) {
            foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP'] as $header) {
                if (!empty($_SERVER[$header])) {
                    $ip = trim(explode(',', $_SERVER[$header])[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return $remoteAddr;
    }

    public function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
    }
}

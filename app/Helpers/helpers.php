<?php
declare(strict_types=1);

use App\Core\Config;

// ─── URL helpers ──────────────────────────────────────────────────────────────

if (!function_exists('url')) {
    /**
     * Generate an absolute URL from a root-relative path.
     * Example: url('/login') → http://localhost/stackvault/login
     */
    function url(string $path = ''): string
    {
        $base = rtrim(Config::get('APP_URL', ''), '/');
        $path = '/' . ltrim($path, '/');
        return $base . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate an asset URL.
     * Example: asset('tabler/css/tabler.min.css') → http://localhost/stackvault/assets/tabler/css/tabler.min.css
     */
    function asset(string $path = ''): string
    {
        $base = rtrim(Config::get('APP_URL', ''), '/');
        return $base . '/assets/' . ltrim($path, '/');
    }
}

// ─── HTML helpers ─────────────────────────────────────────────────────────────

if (!function_exists('e')) {
    /** HTML-encode a string for safe output. */
    function e(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// ─── CSRF helpers ─────────────────────────────────────────────────────────────

if (!function_exists('csrf_token')) {
    /** Get (or generate) the current CSRF token. */
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /** Output a hidden CSRF input field. */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

// ─── Auth helpers ─────────────────────────────────────────────────────────────

if (!function_exists('auth_user')) {
    /** Return the logged-in user array or null. */
    function auth_user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('is_auth')) {
    /** Return true if a user is logged in. */
    function is_auth(): bool
    {
        return !empty($_SESSION['user_id']);
    }
}

if (!function_exists('vault_unlocked')) {
    /** Return true if the vault key is present in session. */
    function vault_unlocked(): bool
    {
        return !empty($_SESSION['vault_key']);
    }
}

// ─── Flash messages ───────────────────────────────────────────────────────────

if (!function_exists('flash')) {
    /** Queue a flash message. */
    function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
    }
}

if (!function_exists('get_flash')) {
    /**
     * Pop and return all flash messages for a given type.
     * @return string[]
     */
    function get_flash(string $type): array
    {
        $messages = $_SESSION['flash'][$type] ?? [];
        unset($_SESSION['flash'][$type]);
        return $messages;
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $type): bool
    {
        return !empty($_SESSION['flash'][$type]);
    }
}

// ─── Date helpers ─────────────────────────────────────────────────────────────

if (!function_exists('time_ago')) {
    /**
     * Return a human-readable "time ago" string.
     * Examples: "just now", "5m ago", "3h ago", "2d ago", "4mo ago", "1y ago"
     */
    function time_ago(string $datetime): string
    {
        $diff = (new \DateTime())->diff(new \DateTime($datetime));

        if ($diff->y > 0) return $diff->y . 'y ago';
        if ($diff->m > 0) return $diff->m . 'mo ago';
        if ($diff->d > 0) return $diff->d . 'd ago';
        if ($diff->h > 0) return $diff->h . 'h ago';
        if ($diff->i > 0) return $diff->i . 'm ago';
        return 'just now';
    }
}

// ─── Misc ─────────────────────────────────────────────────────────────────────

if (!function_exists('app_name')) {
    function app_name(): string
    {
        return Config::get('APP_NAME', 'StackVault');
    }
}

if (!function_exists('base_path')) {
    /** Return the APP_BASE_PATH (e.g. /stackvault). */
    function base_path(): string
    {
        return Config::get('APP_BASE_PATH', '');
    }
}

<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request, callable $next): void
    {
        // ── 1. Must be logged in ──────────────────────────────────────────────
        if (empty($_SESSION['user_id'])) {
            Response::redirect('/login');
        }

        // ── 2. Inactivity timeout ─────────────────────────────────────────────
        $lifetime = (int) Config::get('SESSION_LIFETIME', 120) * 60;

        if (
            isset($_SESSION['last_activity'])
            && (time() - (int) $_SESSION['last_activity']) > $lifetime
        ) {
            // Wipe vault key (if unlocked) before destroying session
            if (isset($_SESSION['vault_key'])) {
                $_SESSION['vault_key'] = str_repeat("\x00", 32);
            }
            session_unset();
            session_destroy();
            Response::redirect('/login?reason=timeout');
        }

        // ── 3. Touch activity timestamp ───────────────────────────────────────
        $_SESSION['last_activity'] = time();

        $next($request);
    }
}

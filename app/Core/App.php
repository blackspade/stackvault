<?php
declare(strict_types=1);

namespace App\Core;

class App
{
    public function __construct()
    {
        $this->bootstrap();
    }

    // ─── Bootstrap ────────────────────────────────────────────────────────────

    private function bootstrap(): void
    {
        // 1. Load .env — redirect to setup if missing
        $envFile = SV_ROOT . '/.env';
        if (!file_exists($envFile)) {
            $base = dirname(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
            header('Location: ' . rtrim($base, '/') . '/setup.php');
            exit;
        }

        Config::load($envFile);

        // 2. Redirect to setup if not yet installed
        if (Config::get('APP_INSTALLED') !== 'true') {
            $base = Config::get('APP_BASE_PATH', '');
            header('Location: ' . rtrim($base, '/') . '/setup.php');
            exit;
        }

        // 3. Session configuration
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = (int) Config::get('SESSION_LIFETIME', 120) * 60;
            ini_set('session.name',             'sv_sess');
            ini_set('session.use_strict_mode',  '1');
            ini_set('session.cookie_httponly',  '1');
            ini_set('session.gc_maxlifetime',   (string) $lifetime);
            ini_set('session.cookie_samesite',  'Lax');

            if (Config::get('SESSION_SECURE') === 'true') {
                ini_set('session.cookie_secure', '1');
            }

            session_start();
        }

        // 4. Error & exception handlers
        $this->registerErrorHandlers();

        // 5. Global helpers
        require_once SV_ROOT . '/app/Helpers/helpers.php';
    }

    // ─── Error handlers ───────────────────────────────────────────────────────

    private function registerErrorHandlers(): void
    {
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            if (!(error_reporting() & $errno)) {
                return false;
            }
            $this->logError("[E{$errno}] {$errstr} in {$errfile}:{$errline}");
            if (Config::get('APP_DEBUG') === 'true') {
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
            return true;
        });

        set_exception_handler(function (\Throwable $e): void {
            $this->logError(
                '[' . get_class($e) . '] ' . $e->getMessage()
                . ' in ' . $e->getFile() . ':' . $e->getLine()
            );
            if (Config::get('APP_DEBUG') === 'true') {
                echo '<pre style="background:#1e1e2e;color:#cdd6f4;padding:20px;'
                   . 'margin:0;font-family:monospace;font-size:13px;white-space:pre-wrap">';
                echo '<strong style="color:#f38ba8">' . get_class($e) . '</strong>: ';
                echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "\n\n";
                echo htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
                echo '</pre>';
            } else {
                Response::abort(500, 'An internal error occurred.');
            }
        });
    }

    private function logError(string $message): void
    {
        $dir  = SV_ROOT . '/' . Config::get('LOG_PATH', 'storage/logs');
        $file = $dir . '/app-' . date('Y-m-d') . '.log';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        error_log('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, 3, $file);
    }

    // ─── Run ──────────────────────────────────────────────────────────────────

    public function run(): void
    {
        // Share global view data
        $request = new Request();

        // Allow the settings table to override APP_NAME at runtime
        try {
            $nameOverride = \App\Models\SettingsModel::get('app_name');
            if ($nameOverride !== '') {
                Config::set('APP_NAME', $nameOverride);
            }
        } catch (\Throwable) {
            // Settings table may not exist on a fresh install — silently ignore
        }

        View::shareMany([
            'currentPath' => $request->path(),
            'user'        => $_SESSION['user'] ?? null,
            'appName'     => Config::get('APP_NAME', 'StackVault'),
        ]);

        // Load routes and dispatch
        require_once SV_ROOT . '/routes/web.php';
        Router::dispatch($request);
    }
}

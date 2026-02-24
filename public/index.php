<?php
declare(strict_types=1);
/**
 * StackVault — Front Controller
 * All HTTP requests are routed here via public/.htaccess
 */

define('SV_ROOT', dirname(__DIR__));
define('SV_START', microtime(true));

// ─── Autoloader (PSR-4: App\ → app/) ─────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = SV_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// ─── Bootstrap & dispatch ─────────────────────────────────────────────────────
$app = new App\Core\App();
$app->run();

<?php
declare(strict_types=1);

namespace App\Core;

class Config
{
    private static array $data   = [];
    private static bool  $loaded = false;

    public static function load(string $envFile): void
    {
        if (self::$loaded) {
            return;
        }

        if (!file_exists($envFile)) {
            throw new \RuntimeException('.env file not found. Please run setup.php first.');
        }

        $parsed = self::parseEnvFile($envFile);

        self::$data   = $parsed;
        self::$loaded = true;

        // Apply debug mode
        if (self::get('APP_DEBUG') === 'true') {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
            ini_set('display_errors', '0');
        }

        // Set timezone
        date_default_timezone_set('UTC');
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$data[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$data[$key] = $value;
    }

    public static function all(): array
    {
        return self::$data;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    // ─── Internal ─────────────────────────────────────────────────────────────

    /**
     * Simple line-by-line .env parser.
     * Handles comments starting with #, KEY=VALUE pairs, and optional quoting.
     * Unlike parse_ini_file(), this tolerates any characters in comment lines
     * including !, =, and other special PHP ini characters.
     */
    private static function parseEnvFile(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new \RuntimeException('Could not read .env file.');
        }

        $data = [];
        foreach ($lines as $line) {
            $line = trim($line);

            // Skip blank lines and comment lines
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Must contain = to be a valid KEY=VALUE pair
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }

            $key   = trim(substr($line, 0, $pos));
            $value = trim(substr($line, $pos + 1));

            // Strip optional surrounding quotes (single or double)
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last  = $value[-1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            if ($key !== '') {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}

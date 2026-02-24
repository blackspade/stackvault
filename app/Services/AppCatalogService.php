<?php
declare(strict_types=1);

namespace App\Services;

/**
 * AppCatalogService
 *
 * Reads and indexes the Cloudron-manifest JSON files from assets/app-catalog/.
 * All catalog data is parsed once per request and held in a static cache.
 */
class AppCatalogService
{
    private static ?array $catalog = null;

    // ─── Paths ────────────────────────────────────────────────────────────────

    public static function dataDir(): string
    {
        return dirname(__DIR__, 2) . '/assets/app-catalog';
    }

    public static function iconPath(string $title): string
    {
        return self::dataDir() . '/images/' . $title . '.png';
    }

    public static function iconExists(string $title): bool
    {
        return file_exists(self::iconPath($title));
    }

    // ─── Load / cache ─────────────────────────────────────────────────────────

    public static function getAll(): array
    {
        if (self::$catalog !== null) {
            return self::$catalog;
        }

        $apps = [];

        foreach (glob(self::dataDir() . '/*.json') as $file) {
            $json = file_get_contents($file);
            if (!$json) continue;

            $data = json_decode($json, true);
            if (!$data || !isset($data['manifest']['title'])) continue;

            $apps[] = $data;
        }

        usort($apps, fn($a, $b) => strcasecmp(
            $a['manifest']['title'],
            $b['manifest']['title']
        ));

        return self::$catalog = $apps;
    }

    // ─── Lookups ──────────────────────────────────────────────────────────────

    public static function getById(string $id): ?array
    {
        foreach (self::getAll() as $app) {
            if (($app['id'] ?? '') === $id) {
                return $app;
            }
        }
        return null;
    }

    public static function search(string $query = '', string $tag = ''): array
    {
        $all = self::getAll();

        if ($query !== '') {
            $q   = strtolower($query);
            $all = array_values(array_filter($all, static function (array $app) use ($q): bool {
                return str_contains(strtolower($app['manifest']['title']   ?? ''), $q)
                    || str_contains(strtolower($app['manifest']['tagline'] ?? ''), $q)
                    || str_contains(strtolower(implode(' ', $app['manifest']['tags'] ?? [])), $q);
            }));
        }

        if ($tag !== '') {
            $all = array_values(array_filter($all, static function (array $app) use ($tag): bool {
                return in_array($tag, $app['manifest']['tags'] ?? [], true);
            }));
        }

        return $all;
    }

    // ─── Tag index ────────────────────────────────────────────────────────────

    /** Returns all unique tags sorted by frequency (most common first). */
    public static function getAllTags(): array
    {
        $freq = [];
        foreach (self::getAll() as $app) {
            foreach ($app['manifest']['tags'] ?? [] as $tag) {
                $freq[$tag] = ($freq[$tag] ?? 0) + 1;
            }
        }
        arsort($freq);
        return array_keys($freq);
    }

    // ─── Addon parsing ────────────────────────────────────────────────────────

    /**
     * Return structured badge data for manifest addons.
     * Each badge: ['label' => string, 'icon' => string, 'optional' => bool]
     */
    public static function getAddonBadges(array $addons): array
    {
        $map = [
            'mysql'        => ['MySQL',       'ti-database'],
            'postgresql'   => ['PostgreSQL',  'ti-database'],
            'redis'        => ['Redis',        'ti-bolt'],
            'mongodb'      => ['MongoDB',      'ti-database'],
            'sendmail'     => ['Email',        'ti-mail'],
            'localstorage' => ['Storage',      'ti-folder'],
            'oidc'         => ['SSO / OIDC',   'ti-key'],
            'ldap'         => ['LDAP',         'ti-users'],
            'proxyAuth'    => ['Auth Proxy',   'ti-shield-lock'],
            'scheduler'    => ['Scheduler',    'ti-clock'],
        ];

        $badges = [];
        foreach ($map as $key => [$label, $icon]) {
            if (!isset($addons[$key])) continue;
            $optional = !empty($addons[$key]['optional']);
            $badges[] = ['label' => $label, 'icon' => $icon, 'optional' => $optional];
        }
        return $badges;
    }

    /** Return a comma-separated string of addon names (e.g. "MySQL, Redis, Email"). */
    public static function getAddonString(array $addons): string
    {
        return implode(', ', array_column(self::getAddonBadges($addons), 'label'));
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Format bytes to human-readable string. */
    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)   return round($bytes / 1048576,   1) . ' MB';
        return round($bytes / 1024, 0) . ' KB';
    }

    // ─── Markdown renderer ────────────────────────────────────────────────────

    /**
     * Very simple Markdown → HTML converter for trusted catalog content.
     * Handles: ATX headers, unordered lists, bold, italic, inline code, links.
     */
    public static function markdownToHtml(string $md): string
    {
        $lines  = explode("\n", $md);
        $out    = [];
        $inList = false;

        foreach ($lines as $line) {
            // ATX headers
            if (preg_match('/^### (.+)$/', $line, $m)) {
                if ($inList) { $out[] = '</ul>'; $inList = false; }
                $out[] = '<h6 class="mt-3 mb-1 fw-semibold">' . self::inlineMd($m[1]) . '</h6>';
                continue;
            }
            if (preg_match('/^## (.+)$/', $line, $m)) {
                if ($inList) { $out[] = '</ul>'; $inList = false; }
                $out[] = '<h5 class="mt-3 mb-1 fw-semibold">' . self::inlineMd($m[1]) . '</h5>';
                continue;
            }
            if (preg_match('/^# (.+)$/', $line, $m)) {
                if ($inList) { $out[] = '</ul>'; $inList = false; }
                $out[] = '<h4 class="mt-3 mb-1 fw-semibold">' . self::inlineMd($m[1]) . '</h4>';
                continue;
            }

            // Unordered list items (*, -, •)
            if (preg_match('/^\s*[\*\-•] (.+)$/', $line, $m)) {
                if (!$inList) { $out[] = '<ul class="mb-2 ps-3">'; $inList = true; }
                $out[] = '<li>' . self::inlineMd($m[1]) . '</li>';
                continue;
            }

            // Blank line — close any open list, separate paragraphs
            if (trim($line) === '') {
                if ($inList) { $out[] = '</ul>'; $inList = false; }
                $out[] = '';
                continue;
            }

            // Regular line — paragraph (don't wrap if inside a list context)
            if ($inList) {
                // Continuation line within list — treat as text
                $out[] = self::inlineMd($line);
            } else {
                $out[] = '<p class="mb-2">' . self::inlineMd($line) . '</p>';
            }
        }

        if ($inList) $out[] = '</ul>';

        return implode("\n", array_filter($out, fn($l) => $l !== ''));
    }

    private static function inlineMd(string $text): string
    {
        // Escape HTML entities (preserves & > < etc.)
        $t = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Bold **text**
        $t = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $t);

        // Italic *text* (avoid matching already-replaced **)
        $t = preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $t);

        // Inline code `code`
        $t = preg_replace('/`([^`]+)`/', '<code class="text-pink">$1</code>', $t);

        // Markdown links [label](url)
        $t = preg_replace(
            '/\[([^\]]+)\]\(([^)]+)\)/',
            '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>',
            $t
        );

        return $t;
    }
}

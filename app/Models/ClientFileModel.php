<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ClientFileModel
{
    // ─── Constraints & metadata ───────────────────────────────────────────────

    public const MAX_BYTES = 536870912; // 512 MB

    public const ALLOWED_EXTENSIONS = ['zip', '7z', 'rar', 'tar', 'gz', 'tgz', 'sql'];

    public const EXTENSION_BADGE = [
        'zip' => 'bg-blue-lt text-blue',
        '7z'  => 'bg-purple-lt text-purple',
        'rar' => 'bg-orange-lt text-orange',
        'tar' => 'bg-teal-lt text-teal',
        'gz'  => 'bg-teal-lt text-teal',
        'tgz' => 'bg-teal-lt text-teal',
        'sql' => 'bg-green-lt text-green',
    ];

    public static function extensionBadgeClass(string $ext): string
    {
        return self::EXTENSION_BADGE[strtolower($ext)] ?? 'bg-secondary-lt text-muted';
    }

    // ─── DB migration ─────────────────────────────────────────────────────────

    public static function migrate(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;

        try {
            Database::execute("
                CREATE TABLE IF NOT EXISTS `client_files` (
                    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `client_id`   INT UNSIGNED NOT NULL,
                    `filename`    VARCHAR(500) NOT NULL,
                    `stored_name` VARCHAR(500) NOT NULL,
                    `file_size`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
                    `extension`   VARCHAR(20) NOT NULL,
                    `description` TEXT DEFAULT NULL,
                    `uploaded_by` INT UNSIGNED DEFAULT NULL,
                    `created_at`  DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_cfiles_client` (`client_id`),
                    KEY `idx_cfiles_ext`    (`extension`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ", []);
        } catch (\Throwable) {
            // Table already exists or DB unavailable — silently continue
        }
    }

    // ─── Storage helpers ──────────────────────────────────────────────────────

    /** Returns the storage directory for a client, creating it if needed. */
    public static function ensureStorageDir(int $clientId): string
    {
        $dir = self::baseStorageDir() . '/' . $clientId;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);

            // Block direct HTTP access
            file_put_contents($dir . '/.htaccess', "Deny from all\n");
        }

        return $dir;
    }

    /** Full filesystem path to a stored file. */
    public static function storagePath(int $clientId, string $storedName): string
    {
        return self::baseStorageDir() . '/' . $clientId . '/' . $storedName;
    }

    private static function baseStorageDir(): string
    {
        // app/Models → app → project root → storage/client_files
        return dirname(__DIR__, 2) . '/storage/client_files';
    }

    // ─── MIME type for download header ────────────────────────────────────────

    public static function mimeType(string $ext): string
    {
        return match (strtolower($ext)) {
            'zip' => 'application/zip',
            '7z'  => 'application/x-7z-compressed',
            'rar' => 'application/x-rar-compressed',
            'tar' => 'application/x-tar',
            'gz'  => 'application/gzip',
            'tgz' => 'application/x-compressed-tar',
            'sql' => 'application/sql',
            default => 'application/octet-stream',
        };
    }

    // ─── Formatting helpers ───────────────────────────────────────────────────

    public static function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)   return round($bytes / 1048576,   1) . ' MB';
        if ($bytes >= 1024)      return round($bytes / 1024,       1) . ' KB';
        return $bytes . ' B';
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAll(string $search = '', int $clientId = 0, string $ext = ''): array
    {
        self::migrate();

        $where  = [];
        $params = [];

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(cf.filename LIKE ? OR cf.description LIKE ? OR c.name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($clientId > 0) {
            $where[]  = 'cf.client_id = ?';
            $params[] = $clientId;
        }

        if ($ext !== '' && in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            $where[]  = 'cf.extension = ?';
            $params[] = $ext;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll("
            SELECT cf.*, c.name AS client_name, u.username AS uploader
            FROM `client_files` cf
            LEFT JOIN `clients` c ON c.id = cf.client_id
            LEFT JOIN `users`   u ON u.id = cf.uploaded_by
            {$whereClause}
            ORDER BY cf.created_at DESC
        ", $params);
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        self::migrate();

        return Database::fetchOne("
            SELECT cf.*, c.name AS client_name, u.username AS uploader
            FROM `client_files` cf
            LEFT JOIN `clients` c ON c.id = cf.client_id
            LEFT JOIN `users`   u ON u.id = cf.uploaded_by
            WHERE cf.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        self::migrate();

        return Database::insert(
            "INSERT INTO `client_files`
                (client_id, filename, stored_name, file_size, extension, description, uploaded_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $data['client_id'],
                $data['filename'],
                $data['stored_name'],
                $data['file_size'],
                $data['extension'],
                $data['description'] ?: null,
                $data['uploaded_by'] ?? null,
            ]
        );
    }

    public static function updateDescription(int $id, string $description): void
    {
        Database::execute(
            "UPDATE `client_files` SET description = ? WHERE id = ?",
            [$description ?: null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `client_files` WHERE id = ?", [$id]);
    }

    // ─── Activity ─────────────────────────────────────────────────────────────

    public static function getActivity(int $fileId, int $limit = 25): array
    {
        return Database::fetchAll("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            WHERE a.entity_type = 'client_file' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$fileId, $limit]);
    }
}

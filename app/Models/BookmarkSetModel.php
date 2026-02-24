<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class BookmarkSetModel
{
    // ─── Browser constants ────────────────────────────────────────────────────

    public const BROWSERS = [
        'chrome'  => 'Google Chrome',
        'edge'    => 'Microsoft Edge',
        'firefox' => 'Mozilla Firefox',
        'safari'  => 'Apple Safari',
        'other'   => 'Other',
    ];

    public static function browserBadgeClass(string $browser): string
    {
        return match ($browser) {
            'chrome'  => 'bg-blue-lt text-blue',
            'edge'    => 'bg-cyan-lt text-cyan',
            'firefox' => 'bg-orange-lt text-orange',
            'safari'  => 'bg-teal-lt text-teal',
            default   => 'bg-secondary-lt text-muted',
        };
    }

    // ─── DB migration ─────────────────────────────────────────────────────────

    public static function migrate(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;

        try {
            Database::execute("
                CREATE TABLE IF NOT EXISTS `bookmark_sets` (
                    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `client_id`   INT UNSIGNED DEFAULT NULL,
                    `name`        VARCHAR(255) NOT NULL,
                    `browser`     VARCHAR(50) NOT NULL DEFAULT 'other',
                    `description` TEXT DEFAULT NULL,
                    `created_by`  INT UNSIGNED DEFAULT NULL,
                    `created_at`  DATETIME NOT NULL,
                    `updated_at`  DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_bset_client` (`client_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ", []);

            Database::execute("
                CREATE TABLE IF NOT EXISTS `bookmark_folders` (
                    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `set_id`     INT UNSIGNED NOT NULL,
                    `name`       VARCHAR(255) NOT NULL,
                    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_bfolder_set` (`set_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ", []);

            Database::execute("
                CREATE TABLE IF NOT EXISTS `bookmarks` (
                    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `set_id`     INT UNSIGNED NOT NULL,
                    `folder_id`  INT UNSIGNED DEFAULT NULL,
                    `title`      VARCHAR(500) NOT NULL,
                    `url`        TEXT NOT NULL,
                    `favicon`    MEDIUMTEXT DEFAULT NULL,
                    `add_date`   INT UNSIGNED DEFAULT NULL,
                    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
                    `created_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_bmark_set`    (`set_id`),
                    KEY `idx_bmark_folder` (`folder_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ", []);
        } catch (\Throwable) {
            // Tables already exist or DB unavailable — silently continue
        }
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAll(string $search = '', int $clientId = 0): array
    {
        self::migrate();

        $where  = [];
        $params = [];

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(bs.name LIKE ? OR bs.description LIKE ? OR c.name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($clientId > 0) {
            $where[]  = 'bs.client_id = ?';
            $params[] = $clientId;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll("
            SELECT
                bs.*,
                c.name AS client_name,
                (SELECT COUNT(*) FROM `bookmark_folders` bf WHERE bf.set_id = bs.id) AS folder_count,
                (SELECT COUNT(*) FROM `bookmarks` b       WHERE b.set_id   = bs.id) AS bookmark_count
            FROM `bookmark_sets` bs
            LEFT JOIN `clients` c ON c.id = bs.client_id
            {$whereClause}
            ORDER BY bs.created_at DESC
        ", $params);
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        self::migrate();

        return Database::fetchOne("
            SELECT
                bs.*,
                c.name AS client_name,
                (SELECT COUNT(*) FROM `bookmark_folders` bf WHERE bf.set_id = bs.id) AS folder_count,
                (SELECT COUNT(*) FROM `bookmarks` b       WHERE b.set_id   = bs.id) AS bookmark_count
            FROM `bookmark_sets` bs
            LEFT JOIN `clients` c ON c.id = bs.client_id
            WHERE bs.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        self::migrate();

        return Database::insert(
            "INSERT INTO `bookmark_sets`
                (client_id, name, browser, description, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['client_id']   ?: null,
                $data['name'],
                $data['browser']     ?: 'other',
                $data['description'] ?: null,
                $data['created_by']  ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `bookmark_sets`
             SET client_id = ?, name = ?, browser = ?, description = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['client_id']   ?: null,
                $data['name'],
                $data['browser']     ?: 'other',
                $data['description'] ?: null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `bookmarks`        WHERE set_id = ?", [$id]);
        Database::execute("DELETE FROM `bookmark_folders` WHERE set_id = ?", [$id]);
        Database::execute("DELETE FROM `bookmark_sets`    WHERE id = ?",     [$id]);
    }

    // ─── Activity ─────────────────────────────────────────────────────────────

    public static function getActivity(int $setId, int $limit = 25): array
    {
        return Database::fetchAll("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            WHERE a.entity_type = 'bookmark_set' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$setId, $limit]);
    }
}

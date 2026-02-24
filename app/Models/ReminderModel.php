<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ReminderModel
{
    public const TYPES = [
        'custom'             => 'Custom',
        'domain_expiry'      => 'Domain Expiry',
        'ssl_expiry'         => 'SSL Expiry',
        'server_maintenance' => 'Server Maintenance',
        'license_renewal'    => 'License Renewal',
    ];

    public const TYPE_ICONS = [
        'custom'             => 'ti-bell',
        'domain_expiry'      => 'ti-world',
        'ssl_expiry'         => 'ti-certificate',
        'server_maintenance' => 'ti-server',
        'license_renewal'    => 'ti-file-invoice',
    ];

    public const TYPE_COLORS = [
        'custom'             => 'secondary',
        'domain_expiry'      => 'blue',
        'ssl_expiry'         => 'orange',
        'server_maintenance' => 'indigo',
        'license_renewal'    => 'teal',
    ];

    public static function ensureSchema(): void
    {
        Database::execute("
            CREATE TABLE IF NOT EXISTS `reminders` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `title`      VARCHAR(255) NOT NULL,
                `type`       VARCHAR(50)  NOT NULL DEFAULT 'custom',
                `client_id`  INT UNSIGNED NULL,
                `due_date`   DATE         NOT NULL,
                `notes`      TEXT         NULL,
                `is_done`    TINYINT(1)   NOT NULL DEFAULT 0,
                `done_at`    DATETIME     NULL,
                `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_due_date`  (`due_date`),
                INDEX `idx_is_done`   (`is_done`),
                INDEX `idx_client_id` (`client_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAll(string $status = '', string $type = '', int $clientId = 0): array
    {
        $where  = [];
        $params = [];

        if ($status === 'pending') {
            $where[] = 'r.is_done = 0';
        } elseif ($status === 'done') {
            $where[] = 'r.is_done = 1';
        } elseif ($status === 'overdue') {
            $where[] = 'r.is_done = 0 AND r.due_date < CURDATE()';
        }

        if ($type !== '') {
            $where[]  = 'r.type = ?';
            $params[] = $type;
        }

        if ($clientId > 0) {
            $where[]  = 'r.client_id = ?';
            $params[] = $clientId;
        }

        $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll("
            SELECT r.*, c.name AS client_name,
                   DATEDIFF(r.due_date, CURDATE()) AS days_until
            FROM `reminders` r
            LEFT JOIN `clients` c ON c.id = r.client_id
            {$w}
            ORDER BY r.is_done ASC, r.due_date ASC
        ", $params);
    }

    /** Upcoming (within $days days) + overdue, not yet done — for dashboard widget. */
    public static function getUpcoming(int $days = 30): array
    {
        return Database::fetchAll("
            SELECT r.*, c.name AS client_name,
                   DATEDIFF(r.due_date, CURDATE()) AS days_until
            FROM `reminders` r
            LEFT JOIN `clients` c ON c.id = r.client_id
            WHERE r.is_done = 0
              AND r.due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY r.due_date ASC
        ", [$days]);
    }

    public static function countOverdue(): int
    {
        $row = Database::fetchOne(
            "SELECT COUNT(*) AS n FROM `reminders` WHERE is_done = 0 AND due_date < CURDATE()"
        );
        return (int) ($row['n'] ?? 0);
    }

    // ─── Single ───────────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne("
            SELECT r.*, c.name AS client_name,
                   DATEDIFF(r.due_date, CURDATE()) AS days_until
            FROM `reminders` r
            LEFT JOIN `clients` c ON c.id = r.client_id
            WHERE r.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO `reminders` (title, type, client_id, due_date, notes, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['title'],
                $data['type']      ?? 'custom',
                $data['client_id'] ?: null,
                $data['due_date'],
                $data['notes']     ?: null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `reminders`
             SET title = ?, type = ?, client_id = ?, due_date = ?, notes = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['title'],
                $data['type']      ?? 'custom',
                $data['client_id'] ?: null,
                $data['due_date'],
                $data['notes']     ?: null,
                $id,
            ]
        );
    }

    public static function markDone(int $id): void
    {
        Database::execute(
            "UPDATE `reminders` SET is_done = 1, done_at = NOW(), updated_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    public static function markUndone(int $id): void
    {
        Database::execute(
            "UPDATE `reminders` SET is_done = 0, done_at = NULL, updated_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `reminders` WHERE id = ?", [$id]);
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class HostMachineModel
{
    // ─── OS constants ─────────────────────────────────────────────────────────

    public const OS_TYPES = [
        'mac'     => 'macOS',
        'windows' => 'Windows',
        'linux'   => 'Linux',
        'other'   => 'Other',
    ];

    /** Path where the hosts file lives on each OS. */
    public const OS_HOSTS_PATH = [
        'mac'     => '/etc/hosts',
        'windows' => 'C:\Windows\System32\drivers\etc\hosts',
        'linux'   => '/etc/hosts',
        'other'   => '/etc/hosts',
    ];

    public static function osBadgeClass(string $os): string
    {
        return match ($os) {
            'mac'     => 'bg-secondary-lt text-secondary',
            'windows' => 'bg-blue-lt text-blue',
            'linux'   => 'bg-orange-lt text-orange',
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
                CREATE TABLE IF NOT EXISTS `host_machines` (
                    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `client_id`   INT UNSIGNED DEFAULT NULL,
                    `name`        VARCHAR(255) NOT NULL,
                    `os`          VARCHAR(50) NOT NULL DEFAULT 'other',
                    `hosts_file`  MEDIUMTEXT DEFAULT NULL,
                    `description` TEXT DEFAULT NULL,
                    `created_by`  INT UNSIGNED DEFAULT NULL,
                    `created_at`  DATETIME NOT NULL,
                    `updated_at`  DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    KEY `idx_hm_client` (`client_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ", []);
        } catch (\Throwable) {
            // Table already exists or DB unavailable — silently continue
        }
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAll(string $search = '', int $clientId = 0, string $os = ''): array
    {
        self::migrate();

        $where  = [];
        $params = [];

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(hm.name LIKE ? OR hm.description LIKE ? OR c.name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($clientId > 0) {
            $where[]  = 'hm.client_id = ?';
            $params[] = $clientId;
        }

        if ($os !== '' && array_key_exists($os, self::OS_TYPES)) {
            $where[]  = 'hm.os = ?';
            $params[] = $os;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll("
            SELECT hm.*, c.name AS client_name
            FROM `host_machines` hm
            LEFT JOIN `clients` c ON c.id = hm.client_id
            {$whereClause}
            ORDER BY hm.name ASC
        ", $params);
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        self::migrate();

        return Database::fetchOne("
            SELECT hm.*, c.name AS client_name
            FROM `host_machines` hm
            LEFT JOIN `clients` c ON c.id = hm.client_id
            WHERE hm.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        self::migrate();

        return Database::insert(
            "INSERT INTO `host_machines`
                (client_id, name, os, hosts_file, description, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['client_id']   ?: null,
                $data['name'],
                $data['os']          ?: 'other',
                $data['hosts_file']  ?: null,
                $data['description'] ?: null,
                $data['created_by']  ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `host_machines`
             SET client_id = ?, name = ?, os = ?, hosts_file = ?, description = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['client_id']   ?: null,
                $data['name'],
                $data['os']          ?: 'other',
                $data['hosts_file']  ?: null,
                $data['description'] ?: null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `host_machines` WHERE id = ?", [$id]);
    }

    // ─── Activity ─────────────────────────────────────────────────────────────

    public static function getActivity(int $machineId, int $limit = 25): array
    {
        return Database::fetchAll("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            WHERE a.entity_type = 'host_machine' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$machineId, $limit]);
    }
}

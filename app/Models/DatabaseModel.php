<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class DatabaseModel
{
    // ─── Type definitions ─────────────────────────────────────────────────────

    public const TYPES = [
        'mysql'      => 'MySQL',
        'mariadb'    => 'MariaDB',
        'postgresql' => 'PostgreSQL',
        'sqlite'     => 'SQLite',
        'mssql'      => 'MSSQL',
        'other'      => 'Other',
    ];

    /** Default port for each db_type. */
    public const DEFAULT_PORTS = [
        'mysql'      => 3306,
        'mariadb'    => 3306,
        'postgresql' => 5432,
        'sqlite'     => null,
        'mssql'      => 1433,
        'other'      => null,
    ];

    public static function typeBadgeClass(string $type): string
    {
        return match ($type) {
            'mysql'      => 'bg-orange-lt text-orange',
            'mariadb'    => 'bg-teal-lt text-teal',
            'postgresql' => 'bg-blue-lt text-blue',
            'sqlite'     => 'bg-cyan-lt text-cyan',
            'mssql'      => 'bg-red-lt text-red',
            default      => 'bg-secondary-lt text-muted',
        };
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAll(string $search = '', string $type = '', int $clientId = 0): array
    {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(db.db_name LIKE ? OR db.host LIKE ? OR db.username LIKE ? OR c.name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($type !== '') {
            $where[]  = 'db.db_type = ?';
            $params[] = $type;
        }

        if ($clientId > 0) {
            $where[]  = 'db.client_id = ?';
            $params[] = $clientId;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll("
            SELECT
                db.*,
                c.name       AS client_name,
                s.label      AS server_label,
                a.app_name   AS app_name
            FROM `db_instances` db
            LEFT JOIN `clients`      c ON c.id = db.client_id
            LEFT JOIN `servers`      s ON s.id = db.server_id
            LEFT JOIN `applications` a ON a.id = db.app_id
            {$whereClause}
            ORDER BY db.db_name ASC
        ", $params);
    }

    /** Return [id, db_name, db_type] for <select> dropdowns. */
    public static function getForSelect(): array
    {
        return Database::fetchAll(
            "SELECT id, db_name, db_type FROM `db_instances` ORDER BY db_name ASC"
        );
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne("
            SELECT
                db.*,
                c.name     AS client_name,
                s.label    AS server_label,
                a.app_name AS app_name
            FROM `db_instances` db
            LEFT JOIN `clients`      c ON c.id = db.client_id
            LEFT JOIN `servers`      s ON s.id = db.server_id
            LEFT JOIN `applications` a ON a.id = db.app_id
            WHERE db.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO `db_instances`
                (client_id, server_id, app_id, db_type, host, port,
                 db_name, username, password_encrypted, notes, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['client_id']         ?: null,
                $data['server_id']         ?: null,
                $data['app_id']            ?: null,
                $data['db_type'],
                $data['host']              ?: 'localhost',
                $data['port']              ?: null,
                $data['db_name'],
                $data['username']          ?: null,
                $data['password_encrypted'] ?? null,
                $data['notes']             ?: null,
                $data['created_by']        ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `db_instances`
             SET client_id = ?, server_id = ?, app_id = ?, db_type = ?,
                 host = ?, port = ?, db_name = ?, username = ?,
                 password_encrypted = ?, notes = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['client_id']          ?: null,
                $data['server_id']          ?: null,
                $data['app_id']             ?: null,
                $data['db_type'],
                $data['host']               ?: 'localhost',
                $data['port']               ?: null,
                $data['db_name'],
                $data['username']           ?: null,
                $data['password_encrypted'],
                $data['notes']              ?: null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `db_instances` WHERE id = ?", [$id]);
    }

    // ─── Activity ─────────────────────────────────────────────────────────────

    public static function getActivity(int $dbId, int $limit = 25): array
    {
        return Database::fetchAll("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            WHERE a.entity_type = 'database' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$dbId, $limit]);
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class CredentialModel
{
    // ─── Type definitions ─────────────────────────────────────────────────────

    public const TYPES = [
        'ssh'       => 'SSH',
        'cpanel'    => 'cPanel',
        'database'  => 'Database',
        'email'     => 'Email',
        'api_key'   => 'API Key',
        'registrar' => 'Registrar',
        'cloud'     => 'Cloud',
        'other'     => 'Other',
    ];

    /** Return a Tabler badge CSS class for a credential type. */
    public static function typeBadgeClass(string $type): string
    {
        return match ($type) {
            'ssh'       => 'bg-blue-lt text-blue',
            'cpanel'    => 'bg-orange-lt text-orange',
            'database'  => 'bg-green-lt text-green',
            'email'     => 'bg-secondary-lt text-muted',
            'api_key'   => 'bg-purple-lt text-purple',
            'registrar' => 'bg-teal-lt text-teal',
            'cloud'     => 'bg-cyan-lt text-cyan',
            default     => 'bg-secondary-lt text-muted',
        };
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAll(string $search = '', string $type = '', int $clientId = 0): array
    {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $like     = '%' . $search . '%';
            $where[]  = '(cr.label LIKE ? OR cr.username LIKE ?)';
            $params[] = $like;
            $params[] = $like;
        }

        if ($type !== '') {
            $where[]  = 'cr.credential_type = ?';
            $params[] = $type;
        }

        if ($clientId > 0) {
            $where[]  = 'cr.client_id = ?';
            $params[] = $clientId;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll("
            SELECT
                cr.id, cr.label, cr.credential_type, cr.username, cr.port,
                cr.client_id, cr.server_id, cr.domain_id,
                cr.last_viewed_at, cr.created_at, cr.updated_at,
                c.name        AS client_name,
                s.label       AS server_label,
                d.root_domain AS domain_name
            FROM `credentials` cr
            LEFT JOIN `clients` c ON c.id = cr.client_id
            LEFT JOIN `servers` s ON s.id = cr.server_id
            LEFT JOIN `domains` d ON d.id = cr.domain_id
            {$whereClause}
            ORDER BY cr.label ASC
        ", $params);
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne("
            SELECT
                cr.*,
                c.name        AS client_name,
                s.label       AS server_label,
                d.root_domain AS domain_name,
                a.app_name    AS app_name,
                lv.username   AS last_viewed_by_username
            FROM `credentials` cr
            LEFT JOIN `clients`      c  ON c.id  = cr.client_id
            LEFT JOIN `servers`      s  ON s.id  = cr.server_id
            LEFT JOIN `domains`      d  ON d.id  = cr.domain_id
            LEFT JOIN `applications` a  ON a.id  = cr.app_id
            LEFT JOIN `users`        lv ON lv.id = cr.last_viewed_by
            WHERE cr.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO `credentials`
                (client_id, server_id, domain_id, label, credential_type,
                 username, password_encrypted, port, totp_secret_encrypted,
                 notes, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['client_id'] ?: null,
                $data['server_id'] ?: null,
                $data['domain_id'] ?: null,
                $data['label'],
                $data['credential_type'],
                $data['username']              ?: null,
                $data['password_encrypted']    ?? null,
                $data['port']                  ?: null,
                $data['totp_secret_encrypted'] ?? null,
                $data['notes']                 ?: null,
                $data['created_by']            ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `credentials`
             SET client_id = ?, server_id = ?, domain_id = ?, label = ?,
                 credential_type = ?, username = ?, password_encrypted = ?,
                 port = ?, totp_secret_encrypted = ?, notes = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['client_id'] ?: null,
                $data['server_id'] ?: null,
                $data['domain_id'] ?: null,
                $data['label'],
                $data['credential_type'],
                $data['username']              ?: null,
                $data['password_encrypted'],
                $data['port']                  ?: null,
                $data['totp_secret_encrypted'],
                $data['notes']                 ?: null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `credentials` WHERE id = ?", [$id]);
    }

    public static function markViewed(int $id, int $userId): void
    {
        Database::execute(
            "UPDATE `credentials` SET last_viewed_at = NOW(), last_viewed_by = ? WHERE id = ?",
            [$userId, $id]
        );
    }

    // ─── Activity (for show page tab) ─────────────────────────────────────────

    public static function getActivity(int $credId, int $limit = 25): array
    {
        return Database::fetchAll("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            WHERE a.entity_type = 'credential' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$credId, $limit]);
    }
}

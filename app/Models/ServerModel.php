<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ServerModel
{
    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAll(string $search = ''): array
    {
        $where  = '';
        $params = [];

        if ($search !== '') {
            $like   = '%' . $search . '%';
            $where  = 'WHERE (s.label LIKE ? OR s.ip_address LIKE ? OR s.hostname LIKE ?
                              OR s.provider LIKE ? OR s.os_version LIKE ? OR c.name LIKE ?)';
            $params = [$like, $like, $like, $like, $like, $like];
        }

        return Database::fetchAll("
            SELECT
                s.*,
                c.name AS client_name,
                (SELECT COUNT(*) FROM `applications` a  WHERE a.server_id = s.id) AS app_count,
                (SELECT COUNT(*) FROM `db_instances` db WHERE db.server_id = s.id) AS db_count,
                (SELECT COUNT(*) FROM `credentials`  cr WHERE cr.server_id = s.id) AS cred_count
            FROM `servers` s
            LEFT JOIN `clients` c ON c.id = s.client_id
            {$where}
            ORDER BY s.label ASC
        ", $params);
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne("
            SELECT s.*, c.name AS client_name
            FROM `servers` s
            LEFT JOIN `clients` c ON c.id = s.client_id
            WHERE s.id = ?
        ", [$id]);
    }

    /**
     * Return [id, label, ip_address] for use in <select> dropdowns.
     */
    public static function getForSelect(): array
    {
        return Database::fetchAll(
            "SELECT id, label, ip_address FROM `servers` ORDER BY label ASC"
        );
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO `servers`
                (client_id, label, ip_address, hostname, provider, os_version,
                 ssh_port, monitoring_status, firewall_notes, installed_stacks,
                 notes, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['client_id']         ?: null,
                $data['label'],
                $data['ip_address']        ?: null,
                $data['hostname']          ?: null,
                $data['provider']          ?: null,
                $data['os_version']        ?: null,
                (int) ($data['ssh_port']   ?: 22),
                $data['monitoring_status'] ?: 'unknown',
                $data['firewall_notes']    ?: null,
                $data['installed_stacks']  ?: null,
                $data['notes']             ?: null,
                $data['created_by']        ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `servers`
             SET client_id = ?, label = ?, ip_address = ?, hostname = ?,
                 provider = ?, os_version = ?, ssh_port = ?, monitoring_status = ?,
                 firewall_notes = ?, installed_stacks = ?, notes = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['client_id']         ?: null,
                $data['label'],
                $data['ip_address']        ?: null,
                $data['hostname']          ?: null,
                $data['provider']          ?: null,
                $data['os_version']        ?: null,
                (int) ($data['ssh_port']   ?: 22),
                $data['monitoring_status'] ?: 'unknown',
                $data['firewall_notes']    ?: null,
                $data['installed_stacks']  ?: null,
                $data['notes']             ?: null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `servers` WHERE id = ?", [$id]);
    }

    // ─── Related data (for show page tabs) ───────────────────────────────────

    public static function getApplications(int $serverId): array
    {
        return Database::fetchAll("
            SELECT a.id, a.app_name, a.version, a.stack_type, a.deployment_method,
                   a.install_path, a.git_repo,
                   d.root_domain
            FROM `applications` a
            LEFT JOIN `domains` d ON d.id = a.domain_id
            WHERE a.server_id = ?
            ORDER BY a.app_name ASC
        ", [$serverId]);
    }

    public static function getDatabases(int $serverId): array
    {
        return Database::fetchAll("
            SELECT db.id, db.db_type, db.host, db.port, db.db_name, db.username,
                   a.app_name
            FROM `db_instances` db
            LEFT JOIN `applications` a ON a.id = db.app_id
            WHERE db.server_id = ?
            ORDER BY db.db_name ASC
        ", [$serverId]);
    }

    public static function getDomains(int $serverId): array
    {
        return Database::fetchAll("
            SELECT DISTINCT d.id, d.root_domain, d.registrar, d.expiry_date,
                            d.ssl_expiry, d.is_active
            FROM `domains` d
            INNER JOIN `applications` a ON a.domain_id = d.id
            WHERE a.server_id = ?
            ORDER BY d.root_domain ASC
        ", [$serverId]);
    }

    public static function getCredentials(int $serverId): array
    {
        return Database::fetchAll("
            SELECT id, label, credential_type, username, created_at
            FROM `credentials`
            WHERE server_id = ?
            ORDER BY label ASC
        ", [$serverId]);
    }

    public static function getActivity(int $serverId, int $limit = 25): array
    {
        return Database::fetchAll("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            WHERE a.entity_type = 'server' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$serverId, $limit]);
    }
}

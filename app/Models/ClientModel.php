<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ClientModel
{
    // ─── List ─────────────────────────────────────────────────────────────────

    /**
     * Return all clients, with linked-record counts.
     * Optionally filter by name or contact email.
     */
    public static function getAll(string $search = ''): array
    {
        $where  = '';
        $params = [];

        if ($search !== '') {
            $like   = '%' . $search . '%';
            $where  = 'WHERE (c.name LIKE ? OR c.contact_email LIKE ? OR c.contact_name LIKE ?)';
            $params = [$like, $like, $like];
        }

        return Database::fetchAll("
            SELECT
                c.*,
                (SELECT COUNT(*) FROM `domains`      d  WHERE d.client_id  = c.id AND d.is_active = 1) AS domain_count,
                (SELECT COUNT(*) FROM `servers`      s  WHERE s.client_id  = c.id)                     AS server_count,
                (SELECT COUNT(*) FROM `credentials`  cr WHERE cr.client_id = c.id)                     AS cred_count,
                (SELECT COUNT(*) FROM `applications` a  WHERE a.client_id  = c.id)                     AS app_count
            FROM `clients` c
            {$where}
            ORDER BY c.name ASC
        ", $params);
    }

    /**
     * Return [id, name] pairs for use in <select> dropdowns.
     */
    public static function getForSelect(): array
    {
        return Database::fetchAll(
            "SELECT id, name FROM `clients` WHERE is_active = 1 ORDER BY name ASC"
        );
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM `clients` WHERE id = ?",
            [$id]
        );
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO `clients`
                (name, contact_name, contact_email, contact_phone, website, notes, is_active, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['name'],
                $data['contact_name']  ?: null,
                $data['contact_email'] ?: null,
                $data['contact_phone'] ?: null,
                $data['website']       ?: null,
                $data['notes']         ?: null,
                $data['is_active'] ? 1 : 0,
                $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `clients`
             SET name = ?, contact_name = ?, contact_email = ?, contact_phone = ?,
                 website = ?, notes = ?, is_active = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['name'],
                $data['contact_name']  ?: null,
                $data['contact_email'] ?: null,
                $data['contact_phone'] ?: null,
                $data['website']       ?: null,
                $data['notes']         ?: null,
                $data['is_active'] ? 1 : 0,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `clients` WHERE id = ?", [$id]);
    }

    // ─── Related data (for show page tabs) ───────────────────────────────────

    public static function getDomains(int $clientId): array
    {
        return Database::fetchAll("
            SELECT id, root_domain, registrar, expiry_date, ssl_expiry, is_active
            FROM `domains`
            WHERE client_id = ?
            ORDER BY root_domain ASC
        ", [$clientId]);
    }

    public static function getServers(int $clientId): array
    {
        return Database::fetchAll("
            SELECT id, label, ip_address, hostname, provider, os_version, monitoring_status
            FROM `servers`
            WHERE client_id = ?
            ORDER BY label ASC
        ", [$clientId]);
    }

    public static function getCredentials(int $clientId): array
    {
        return Database::fetchAll("
            SELECT id, label, credential_type, username, created_at
            FROM `credentials`
            WHERE client_id = ?
            ORDER BY label ASC
        ", [$clientId]);
    }

    public static function getApplications(int $clientId): array
    {
        return Database::fetchAll("
            SELECT a.id, a.app_name, a.version, a.stack_type, a.deployment_method,
                   s.label AS server_label
            FROM `applications` a
            LEFT JOIN `servers` s ON s.id = a.server_id
            WHERE a.client_id = ?
            ORDER BY a.app_name ASC
        ", [$clientId]);
    }

    public static function getActivity(int $clientId, int $limit = 25): array
    {
        return Database::fetchAll("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            WHERE a.entity_type = 'client' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$clientId, $limit]);
    }
}

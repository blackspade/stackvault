<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class DomainModel
{
    // ─── List ─────────────────────────────────────────────────────────────────

    /**
     * Return [id, root_domain] pairs for use in <select> dropdowns.
     */
    public static function getForSelect(): array
    {
        return Database::fetchAll(
            "SELECT id, root_domain FROM `domains` WHERE is_active = 1 ORDER BY root_domain ASC"
        );
    }

    /**
     * Return all domains with client name and days-until-expiry columns.
     * Optionally filter by domain name, registrar, or client name.
     */
    public static function getAll(string $search = ''): array
    {
        $where  = '';
        $params = [];

        if ($search !== '') {
            $like   = '%' . $search . '%';
            $where  = 'WHERE (d.root_domain LIKE ? OR d.registrar LIKE ? OR c.name LIKE ?)';
            $params = [$like, $like, $like];
        }

        return Database::fetchAll("
            SELECT
                d.*,
                c.name                              AS client_name,
                DATEDIFF(d.expiry_date, CURDATE())  AS expiry_days_left,
                DATEDIFF(d.ssl_expiry,  CURDATE())  AS ssl_days_left
            FROM `domains` d
            LEFT JOIN `clients` c ON c.id = d.client_id
            {$where}
            ORDER BY d.root_domain ASC
        ", $params);
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne("
            SELECT d.*, c.name AS client_name
            FROM `domains` d
            LEFT JOIN `clients` c ON c.id = d.client_id
            WHERE d.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO `domains`
                (client_id, root_domain, registrar, expiry_date, nameservers,
                 ssl_expiry, is_active, notes, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['client_id']  ?: null,
                $data['root_domain'],
                $data['registrar']  ?: null,
                $data['expiry_date'] ?: null,
                $data['nameservers'] ?: null,
                $data['ssl_expiry']  ?: null,
                $data['is_active'] ? 1 : 0,
                $data['notes']      ?: null,
                $data['created_by'] ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `domains`
             SET client_id = ?, root_domain = ?, registrar = ?, expiry_date = ?,
                 nameservers = ?, ssl_expiry = ?, is_active = ?, notes = ?,
                 updated_at = NOW()
             WHERE id = ?",
            [
                $data['client_id']   ?: null,
                $data['root_domain'],
                $data['registrar']   ?: null,
                $data['expiry_date'] ?: null,
                $data['nameservers'] ?: null,
                $data['ssl_expiry']  ?: null,
                $data['is_active'] ? 1 : 0,
                $data['notes']       ?: null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `domains` WHERE id = ?", [$id]);
    }

    // ─── Related data (for show page tabs) ───────────────────────────────────

    public static function getDnsRecords(int $domainId): array
    {
        return Database::fetchAll("
            SELECT id, record_type, name, value, ttl, priority
            FROM `dns_records`
            WHERE domain_id = ?
            ORDER BY record_type ASC, name ASC
        ", [$domainId]);
    }

    public static function getEmailAccounts(int $domainId): array
    {
        return Database::fetchAll("
            SELECT id, email_address, mail_host, smtp_port, imap_port, webmail_url
            FROM `email_accounts`
            WHERE domain_id = ?
            ORDER BY email_address ASC
        ", [$domainId]);
    }

    public static function getApplications(int $domainId): array
    {
        return Database::fetchAll("
            SELECT a.id, a.app_name, a.version, a.stack_type, a.deployment_method,
                   s.label AS server_label, s.ip_address AS server_ip
            FROM `applications` a
            LEFT JOIN `servers` s ON s.id = a.server_id
            WHERE a.domain_id = ?
            ORDER BY a.app_name ASC
        ", [$domainId]);
    }

    public static function getServers(int $domainId): array
    {
        return Database::fetchAll("
            SELECT DISTINCT s.id, s.label, s.ip_address, s.provider, s.os_version, s.monitoring_status
            FROM `servers` s
            INNER JOIN `applications` a ON a.server_id = s.id
            WHERE a.domain_id = ?
            ORDER BY s.label ASC
        ", [$domainId]);
    }

    public static function getDatabases(int $domainId): array
    {
        return Database::fetchAll("
            SELECT db.id, db.db_type, db.host, db.port, db.db_name, db.username,
                   a.app_name
            FROM `db_instances` db
            INNER JOIN `applications` a ON a.id = db.app_id
            WHERE a.domain_id = ?
            ORDER BY db.db_name ASC
        ", [$domainId]);
    }

    public static function getCredentials(int $domainId): array
    {
        return Database::fetchAll("
            SELECT id, label, credential_type, username, created_at
            FROM `credentials`
            WHERE domain_id = ?
            ORDER BY label ASC
        ", [$domainId]);
    }

    public static function getActivity(int $domainId, int $limit = 25): array
    {
        return Database::fetchAll("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            WHERE a.entity_type = 'domain' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$domainId, $limit]);
    }
}

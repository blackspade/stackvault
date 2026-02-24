<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ApplicationModel
{
    // ─── One-time schema migration ────────────────────────────────────────────

    /**
     * Adds `catalog_id` column to the applications table if not already present.
     * Called once per request from ApplicationController constructor.
     */
    public static function migrate(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;

        try {
            Database::execute(
                "ALTER TABLE `applications`
                 ADD COLUMN `catalog_id` VARCHAR(255) DEFAULT NULL AFTER `app_name`"
            );
        } catch (\Throwable) {
            // Column already exists — safe to ignore
        }
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAll(string $search = ''): array
    {
        $where  = '';
        $params = [];

        if ($search !== '') {
            $like   = '%' . $search . '%';
            $where  = 'WHERE (a.app_name LIKE ? OR a.stack_type LIKE ? OR c.name LIKE ? OR s.label LIKE ?)';
            $params = [$like, $like, $like, $like];
        }

        return Database::fetchAll("
            SELECT
                a.*,
                c.name        AS client_name,
                s.label       AS server_label,
                d.root_domain AS domain_name,
                (SELECT COUNT(*) FROM `credentials` cr WHERE cr.app_id = a.id) AS cred_count
            FROM `applications` a
            LEFT JOIN `clients` c ON c.id = a.client_id
            LEFT JOIN `servers` s ON s.id = a.server_id
            LEFT JOIN `domains` d ON d.id = a.domain_id
            {$where}
            ORDER BY a.app_name ASC
        ", $params);
    }

    /** Return [id, app_name] pairs for <select> dropdowns. */
    public static function getForSelect(): array
    {
        return Database::fetchAll("SELECT id, app_name FROM `applications` ORDER BY app_name ASC");
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne("
            SELECT
                a.*,
                c.name        AS client_name,
                s.label       AS server_label,
                d.root_domain AS domain_name
            FROM `applications` a
            LEFT JOIN `clients` c ON c.id = a.client_id
            LEFT JOIN `servers` s ON s.id = a.server_id
            LEFT JOIN `domains` d ON d.id = a.domain_id
            WHERE a.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO `applications`
                (client_id, server_id, domain_id, app_name, catalog_id, version,
                 stack_type, install_path, git_repo, deployment_method,
                 notes, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['client_id']         ?: null,
                $data['server_id']         ?: null,
                $data['domain_id']         ?: null,
                $data['app_name'],
                $data['catalog_id']        ?: null,
                $data['version']           ?: null,
                $data['stack_type']        ?: null,
                $data['install_path']      ?: null,
                $data['git_repo']          ?: null,
                $data['deployment_method'] ?: null,
                $data['notes']             ?: null,
                $data['created_by']        ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `applications`
             SET client_id = ?, server_id = ?, domain_id = ?, app_name = ?,
                 catalog_id = ?, version = ?, stack_type = ?,
                 install_path = ?, git_repo = ?, deployment_method = ?,
                 notes = ?, updated_at = NOW()
             WHERE id = ?",
            [
                $data['client_id']         ?: null,
                $data['server_id']         ?: null,
                $data['domain_id']         ?: null,
                $data['app_name'],
                $data['catalog_id']        ?: null,
                $data['version']           ?: null,
                $data['stack_type']        ?: null,
                $data['install_path']      ?: null,
                $data['git_repo']          ?: null,
                $data['deployment_method'] ?: null,
                $data['notes']             ?: null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `applications` WHERE id = ?", [$id]);
    }

    // ─── Related data (for show page tabs) ───────────────────────────────────

    public static function getCredentials(int $appId): array
    {
        return Database::fetchAll("
            SELECT id, label, credential_type, username, created_at
            FROM `credentials`
            WHERE app_id = ?
            ORDER BY label ASC
        ", [$appId]);
    }

    public static function getActivity(int $appId, int $limit = 25): array
    {
        return Database::fetchAll("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            WHERE a.entity_type = 'application' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$appId, $limit]);
    }
}

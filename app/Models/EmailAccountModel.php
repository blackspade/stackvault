<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class EmailAccountModel
{
    // ─── Common port presets ──────────────────────────────────────────────────

    public const DEFAULT_SMTP = 587;
    public const DEFAULT_IMAP = 993;

    public const SMTP_PRESETS = [
        587  => 'SMTP (TLS/STARTTLS) — 587',
        465  => 'SMTP (SSL) — 465',
        25   => 'SMTP (plain) — 25',
        2525 => 'SMTP (alt) — 2525',
    ];

    public const IMAP_PRESETS = [
        993 => 'IMAP (SSL) — 993',
        143 => 'IMAP (STARTTLS) — 143',
    ];

    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAll(
        string $search   = '',
        int    $clientId = 0,
        int    $domainId = 0
    ): array {
        $where  = [];
        $params = [];

        if ($search !== '') {
            $like    = '%' . $search . '%';
            $where[] = '(e.email_address LIKE ? OR e.mail_host LIKE ? OR e.username LIKE ? OR c.name LIKE ? OR d.root_domain LIKE ?)';
            $params  = array_merge($params, [$like, $like, $like, $like, $like]);
        }

        if ($clientId > 0) {
            $where[]  = 'e.client_id = ?';
            $params[] = $clientId;
        }

        if ($domainId > 0) {
            $where[]  = 'e.domain_id = ?';
            $params[] = $domainId;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll("
            SELECT e.*, c.name AS client_name, d.root_domain
            FROM `email_accounts` e
            LEFT JOIN `clients` c ON c.id = e.client_id
            LEFT JOIN `domains` d ON d.id = e.domain_id
            {$whereClause}
            ORDER BY e.email_address ASC
        ", $params);
    }

    /** Return [id, email_address] for <select> dropdowns. */
    public static function getForSelect(): array
    {
        return Database::fetchAll(
            "SELECT id, email_address FROM `email_accounts` ORDER BY email_address ASC"
        );
    }

    // ─── Single record ────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne("
            SELECT e.*, c.name AS client_name, d.root_domain
            FROM `email_accounts` e
            LEFT JOIN `clients` c ON c.id = e.client_id
            LEFT JOIN `domains` d ON d.id = e.domain_id
            WHERE e.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert(
            "INSERT INTO `email_accounts`
                (client_id, domain_id, email_address, mail_host, smtp_port, imap_port,
                 username, password_encrypted, webmail_url, notes, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $data['client_id']          ?: null,
                $data['domain_id']          ?: null,
                $data['email_address'],
                $data['mail_host']          ?: null,
                $data['smtp_port']          ?: self::DEFAULT_SMTP,
                $data['imap_port']          ?: self::DEFAULT_IMAP,
                $data['username']           ?: null,
                $data['password_encrypted'] ?? null,
                $data['webmail_url']        ?: null,
                $data['notes']             ?: null,
                $data['created_by']        ?? null,
            ]
        );
    }

    public static function update(int $id, array $data): void
    {
        Database::execute(
            "UPDATE `email_accounts`
             SET client_id = ?, domain_id = ?, email_address = ?, mail_host = ?,
                 smtp_port = ?, imap_port = ?, username = ?,
                 password_encrypted = ?, webmail_url = ?, notes = ?,
                 updated_at = NOW()
             WHERE id = ?",
            [
                $data['client_id']          ?: null,
                $data['domain_id']          ?: null,
                $data['email_address'],
                $data['mail_host']          ?: null,
                $data['smtp_port']          ?: self::DEFAULT_SMTP,
                $data['imap_port']          ?: self::DEFAULT_IMAP,
                $data['username']           ?: null,
                $data['password_encrypted'],
                $data['webmail_url']        ?: null,
                $data['notes']             ?: null,
                $id,
            ]
        );
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `email_accounts` WHERE id = ?", [$id]);
    }

    // ─── Activity ─────────────────────────────────────────────────────────────

    public static function getActivity(int $emailId, int $limit = 25): array
    {
        return Database::fetchAll("
            SELECT a.id, a.action, a.description, a.ip_address, a.created_at, u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            WHERE a.entity_type = 'email_account' AND a.entity_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$emailId, $limit]);
    }
}

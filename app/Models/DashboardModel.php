<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class DashboardModel
{
    /**
     * Aggregate counts for the stat cards.
     * All queries run as a single round-trip.
     */
    public static function getStats(): array
    {
        $row = Database::fetchOne("
            SELECT
                (SELECT COUNT(*) FROM `clients`       WHERE is_active = 1) AS clients,
                (SELECT COUNT(*) FROM `domains`       WHERE is_active = 1) AS domains,
                (SELECT COUNT(*) FROM `servers`)                           AS servers,
                (SELECT COUNT(*) FROM `credentials`)                       AS credentials,
                (SELECT COUNT(*) FROM `db_instances`)                      AS db_instances,
                (SELECT COUNT(*) FROM `email_accounts`)                    AS email_accounts,
                (SELECT COUNT(*) FROM `applications`)                      AS applications
        ");

        return $row ?? [
            'clients'       => 0,
            'domains'       => 0,
            'servers'       => 0,
            'credentials'   => 0,
            'db_instances'  => 0,
            'email_accounts'=> 0,
            'applications'  => 0,
        ];
    }

    /**
     * Domains whose registration expires within $days days.
     */
    public static function getExpiringDomains(int $days = 30): array
    {
        return Database::fetchAll("
            SELECT
                d.id,
                d.root_domain,
                d.registrar,
                d.expiry_date,
                DATEDIFF(d.expiry_date, CURDATE()) AS days_left,
                c.name AS client_name
            FROM `domains` d
            LEFT JOIN `clients` c ON c.id = d.client_id
            WHERE d.is_active = 1
              AND d.expiry_date IS NOT NULL
              AND d.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY d.expiry_date ASC
        ", [$days]);
    }

    /**
     * Domains whose SSL certificate expires within $days days.
     */
    public static function getExpiringSsl(int $days = 30): array
    {
        return Database::fetchAll("
            SELECT
                d.id,
                d.root_domain,
                d.ssl_expiry,
                DATEDIFF(d.ssl_expiry, CURDATE()) AS days_left,
                c.name AS client_name
            FROM `domains` d
            LEFT JOIN `clients` c ON c.id = d.client_id
            WHERE d.is_active = 1
              AND d.ssl_expiry IS NOT NULL
              AND d.ssl_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY d.ssl_expiry ASC
        ", [$days]);
    }

    /**
     * Most recently added credentials (labels only — no decryption).
     */
    public static function getRecentCredentials(int $limit = 5): array
    {
        return Database::fetchAll("
            SELECT
                cr.id,
                cr.label,
                cr.credential_type,
                cr.created_at,
                c.name AS client_name
            FROM `credentials` cr
            LEFT JOIN `clients` c ON c.id = cr.client_id
            ORDER BY cr.created_at DESC
            LIMIT ?
        ", [$limit]);
    }

    /**
     * Server count grouped by OS family (os_version field).
     */
    public static function getServerOsBreakdown(int $limit = 8): array
    {
        return Database::fetchAll("
            SELECT
                COALESCE(NULLIF(TRIM(os_version), ''), 'Unknown') AS os,
                COUNT(*) AS count
            FROM `servers`
            GROUP BY os
            ORDER BY count DESC
            LIMIT ?
        ", [$limit]);
    }

    /**
     * Number of failed login attempts in the last 24 hours.
     */
    public static function getFailedLoginCount24h(): int
    {
        $row = Database::fetchOne("
            SELECT COUNT(*) AS cnt
            FROM `activity_logs`
            WHERE action = 'login_failed'
              AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Most recent failed login log entries.
     */
    public static function getRecentFailedLogins(int $limit = 8): array
    {
        return Database::fetchAll("
            SELECT
                id,
                description,
                ip_address,
                created_at
            FROM `activity_logs`
            WHERE action = 'login_failed'
            ORDER BY created_at DESC
            LIMIT ?
        ", [$limit]);
    }

    /**
     * Upcoming reminders (overdue + due within $days days), not yet done.
     * Returns empty array if the reminders table does not exist yet.
     */
    public static function getUpcomingReminders(int $days = 30): array
    {
        try {
            return Database::fetchAll("
                SELECT r.*, c.name AS client_name,
                       DATEDIFF(r.due_date, CURDATE()) AS days_until
                FROM `reminders` r
                LEFT JOIN `clients` c ON c.id = r.client_id
                WHERE r.is_done = 0
                  AND r.due_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY r.due_date ASC
            ", [$days]);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * M365 billing records due within their license's remind_days window (or overdue),
     * not yet paid and not acknowledged. Returns empty array if tables don't exist.
     */
    public static function getM365BillingDue(): array
    {
        try {
            return \App\Models\M365BillingModel::getDueDashboard();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Most recent activity log entries with username.
     */
    public static function getRecentActivity(int $limit = 10): array
    {
        return Database::fetchAll("
            SELECT
                a.id,
                a.action,
                a.entity_type,
                a.entity_id,
                a.description,
                a.ip_address,
                a.created_at,
                u.username
            FROM `activity_logs` a
            LEFT JOIN `users` u ON u.id = a.user_id
            ORDER BY a.created_at DESC
            LIMIT ?
        ", [$limit]);
    }
}

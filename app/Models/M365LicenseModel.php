<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class M365LicenseModel
{
    // ─── Constants ────────────────────────────────────────────────────────────

    public const BUILTIN_PLANS = [
        'Secure Business Professional',
        'Email Essentials with Security',
        'Secure Online Essentials',
        'Email Plus with Security',
    ];

    public const SOURCES = [
        'vendor' => 'Vendor-provided',
        'self'   => 'Self-registered',
    ];

    public const SOURCE_COLORS = [
        'vendor' => 'green',
        'self'   => 'secondary',
    ];

    public const SOURCE_ICONS = [
        'vendor' => 'ti-building-store',
        'self'   => 'ti-user',
    ];

    public const INTERVALS = [
        'monthly'   => 'Monthly',
        'quarterly' => 'Quarterly',
        'custom'    => 'Custom',
    ];

    // ─── Schema ───────────────────────────────────────────────────────────────

    public static function ensureSchema(): void
    {
        Database::execute("
            CREATE TABLE IF NOT EXISTS `m365_licenses` (
                `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `client_id`          INT UNSIGNED NOT NULL,
                `plan`               VARCHAR(100) NOT NULL,
                `license_source`     ENUM('vendor','self') NOT NULL DEFAULT 'vendor',
                `expiry_date`        DATE NULL,
                `seats`              SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                `amount`             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `billing_interval`   ENUM('monthly','quarterly','custom') NOT NULL DEFAULT 'monthly',
                `billing_days`       SMALLINT UNSIGNED NOT NULL DEFAULT 30,
                `remind_days`        TINYINT UNSIGNED NOT NULL DEFAULT 5,
                `next_billing_date`  DATE NULL,
                `is_active`          TINYINT(1) NOT NULL DEFAULT 1,
                `notes`              TEXT NULL,
                `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_client`       (`client_id`),
                INDEX `idx_next_billing` (`next_billing_date`),
                INDEX `idx_is_active`    (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        Database::execute("
            CREATE TABLE IF NOT EXISTS `m365_billing_records` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `license_id`      INT UNSIGNED NOT NULL,
                `period_label`    VARCHAR(100) NOT NULL,
                `period_start`    DATE NOT NULL,
                `period_end`      DATE NOT NULL,
                `due_date`        DATE NOT NULL,
                `amount`          DECIMAL(10,2) NOT NULL,
                `is_acknowledged` TINYINT(1) NOT NULL DEFAULT 0,
                `is_paid`         TINYINT(1) NOT NULL DEFAULT 0,
                `paid_at`         DATETIME NULL,
                `notes`           TEXT NULL,
                `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_license` (`license_id`),
                INDEX `idx_due`     (`due_date`),
                INDEX `idx_paid`    (`is_paid`),
                INDEX `idx_ack`     (`is_acknowledged`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAll(
        string $clientId = '',
        string $plan     = '',
        string $source   = '',
        string $status   = 'active'
    ): array {
        $where  = [];
        $params = [];

        if ($status === 'active') {
            $where[] = 'l.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'l.is_active = 0';
        }

        if ($clientId !== '') {
            $where[]  = 'l.client_id = ?';
            $params[] = (int) $clientId;
        }

        if ($plan !== '') {
            $where[]  = 'l.plan = ?';
            $params[] = $plan;
        }

        if ($source !== '') {
            $where[]  = 'l.license_source = ?';
            $params[] = $source;
        }

        $w = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll("
            SELECT l.*,
                   c.name AS client_name,
                   DATEDIFF(l.expiry_date, CURDATE()) AS expiry_days,
                   (SELECT COUNT(*) FROM `m365_billing_records` br
                    WHERE br.license_id = l.id AND br.is_paid = 0)            AS unpaid_count,
                   (SELECT COALESCE(SUM(br2.amount), 0.00) FROM `m365_billing_records` br2
                    WHERE br2.license_id = l.id AND br2.is_paid = 0)          AS unpaid_total
            FROM `m365_licenses` l
            LEFT JOIN `clients` c ON c.id = l.client_id
            {$w}
            ORDER BY l.is_active DESC, c.name ASC, l.plan ASC
        ", $params);
    }

    // ─── Stats (unfiltered, for index cards) ──────────────────────────────────

    public static function getStats(): array
    {
        $row = Database::fetchOne("
            SELECT
                (SELECT COUNT(*)
                 FROM `m365_licenses`
                 WHERE is_active = 1)                                                    AS active_count,
                (SELECT COUNT(*)
                 FROM `m365_billing_records`
                 WHERE is_paid = 0)                                                      AS unpaid_count,
                (SELECT COUNT(*)
                 FROM `m365_licenses`
                 WHERE is_active = 1
                   AND expiry_date IS NOT NULL
                   AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 0 AND 60)               AS expiring_soon
        ");

        // Monthly recurring total — normalise all intervals
        $licenses        = Database::fetchAll(
            "SELECT amount, billing_interval, billing_days FROM `m365_licenses` WHERE is_active = 1"
        );
        $monthlyRecurring = 0.0;
        foreach ($licenses as $l) {
            $amt = (float) $l['amount'];
            $monthlyRecurring += match ($l['billing_interval']) {
                'quarterly' => $amt / 3,
                'custom'    => (int) $l['billing_days'] > 0
                    ? round($amt * 30 / (int) $l['billing_days'], 2)
                    : $amt,
                default     => $amt,
            };
        }

        return [
            'active_count'     => (int)   ($row['active_count']  ?? 0),
            'unpaid_count'     => (int)   ($row['unpaid_count']   ?? 0),
            'expiring_soon'    => (int)   ($row['expiring_soon']  ?? 0),
            'monthly_recurring'=> round($monthlyRecurring, 2),
        ];
    }

    // ─── Single ───────────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne("
            SELECT l.*,
                   c.name AS client_name,
                   DATEDIFF(l.expiry_date, CURDATE()) AS expiry_days,
                   (SELECT COUNT(*) FROM `m365_billing_records` br
                    WHERE br.license_id = l.id AND br.is_paid = 0)            AS unpaid_count,
                   (SELECT COALESCE(SUM(br2.amount), 0.00) FROM `m365_billing_records` br2
                    WHERE br2.license_id = l.id AND br2.is_paid = 0)          AS unpaid_total
            FROM `m365_licenses` l
            LEFT JOIN `clients` c ON c.id = l.client_id
            WHERE l.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(array $data): int
    {
        return Database::insert("
            INSERT INTO `m365_licenses`
                (client_id, plan, license_source, expiry_date, seats, amount,
                 billing_interval, billing_days, remind_days, next_billing_date,
                 is_active, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())
        ", [
            $data['client_id'],
            $data['plan'],
            $data['license_source'],
            $data['expiry_date']       ?: null,
            $data['seats'],
            $data['amount'],
            $data['billing_interval'],
            $data['billing_days'],
            $data['remind_days'],
            $data['next_billing_date'] ?: null,
            $data['notes']             ?: null,
        ]);
    }

    public static function update(int $id, array $data): void
    {
        Database::execute("
            UPDATE `m365_licenses`
            SET client_id = ?, plan = ?, license_source = ?, expiry_date = ?,
                seats = ?, amount = ?, billing_interval = ?, billing_days = ?,
                remind_days = ?, next_billing_date = ?, notes = ?, updated_at = NOW()
            WHERE id = ?
        ", [
            $data['client_id'],
            $data['plan'],
            $data['license_source'],
            $data['expiry_date']       ?: null,
            $data['seats'],
            $data['amount'],
            $data['billing_interval'],
            $data['billing_days'],
            $data['remind_days'],
            $data['next_billing_date'] ?: null,
            $data['notes']             ?: null,
            $id,
        ]);
    }

    public static function delete(int $id): void
    {
        Database::execute("DELETE FROM `m365_billing_records` WHERE license_id = ?", [$id]);
        Database::execute("DELETE FROM `m365_licenses` WHERE id = ?", [$id]);
    }

    public static function toggleActive(int $id): bool
    {
        Database::execute(
            "UPDATE `m365_licenses` SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?",
            [$id]
        );
        $row = Database::fetchOne("SELECT is_active FROM `m365_licenses` WHERE id = ?", [$id]);
        return (bool) ($row['is_active'] ?? false);
    }

    // ─── Auto-generation helpers ───────────────────────────────────────────────

    /** Active licenses whose next_billing_date has arrived. */
    public static function getDueBillingLicenses(): array
    {
        return Database::fetchAll("
            SELECT * FROM `m365_licenses`
            WHERE is_active = 1
              AND next_billing_date IS NOT NULL
              AND next_billing_date <= CURDATE()
        ");
    }

    /** Advance a license's next_billing_date by one interval. */
    public static function advanceNextBillingDate(int $id, string $interval, int $billingDays, string $currentDate): void
    {
        $dt = new \DateTime($currentDate);
        match ($interval) {
            'quarterly' => $dt->modify('+3 months'),
            'custom'    => $dt->modify('+' . $billingDays . ' days'),
            default     => $dt->modify('+1 month'),
        };
        Database::execute(
            "UPDATE `m365_licenses` SET next_billing_date = ?, updated_at = NOW() WHERE id = ?",
            [$dt->format('Y-m-d'), $id]
        );
    }

    // ─── Datalist helper ──────────────────────────────────────────────────────

    /** All distinct plan names from the DB (for datalist suggestions). */
    public static function getDistinctPlans(): array
    {
        $rows = Database::fetchAll(
            "SELECT DISTINCT plan FROM `m365_licenses` ORDER BY plan ASC"
        );
        return array_column($rows, 'plan');
    }
}

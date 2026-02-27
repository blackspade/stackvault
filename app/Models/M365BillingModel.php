<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class M365BillingModel
{
    // ─── Period helpers ───────────────────────────────────────────────────────

    /**
     * Build period label, start, and end dates from a due_date + interval.
     * Convention: the billing period is the interval BEFORE the due date
     * (e.g. due Feb 26 → billing period = January).
     */
    public static function buildPeriod(string $dueDate, string $interval, int $billingDays): array
    {
        $due = new \DateTime($dueDate);

        if ($interval === 'monthly') {
            // Period = the calendar month before the due date's month
            $periodEnd   = new \DateTime($due->format('Y-m-01'));
            $periodEnd->modify('-1 day');                          // last day of previous month
            $periodStart = new \DateTime($periodEnd->format('Y-m-01')); // first day of that month
            $label       = $periodStart->format('F Y');            // "January 2026"

        } elseif ($interval === 'quarterly') {
            // Period = the calendar quarter before the quarter the due date falls in
            $month = (int) $due->format('n');
            $year  = (int) $due->format('Y');

            if ($month <= 3) {
                $periodStart = new \DateTime(($year - 1) . '-10-01');
                $periodEnd   = new \DateTime(($year - 1) . '-12-31');
                $label       = 'Q4 ' . ($year - 1);
            } elseif ($month <= 6) {
                $periodStart = new \DateTime($year . '-01-01');
                $periodEnd   = new \DateTime($year . '-03-31');
                $label       = 'Q1 ' . $year;
            } elseif ($month <= 9) {
                $periodStart = new \DateTime($year . '-04-01');
                $periodEnd   = new \DateTime($year . '-06-30');
                $label       = 'Q2 ' . $year;
            } else {
                $periodStart = new \DateTime($year . '-07-01');
                $periodEnd   = new \DateTime($year . '-09-30');
                $label       = 'Q3 ' . $year;
            }

        } else {
            // Custom: period = billing_days days immediately before the due date
            $periodEnd   = clone $due;
            $periodEnd->modify('-1 day');
            $periodStart = clone $due;
            $periodStart->modify('-' . $billingDays . ' days');
            $label       = $periodStart->format('M j') . ' – ' . $periodEnd->format('M j, Y');
        }

        return [
            'label' => $label,
            'start' => $periodStart->format('Y-m-d'),
            'end'   => $periodEnd->format('Y-m-d'),
        ];
    }

    // ─── List ─────────────────────────────────────────────────────────────────

    public static function getAllForLicense(int $licenseId): array
    {
        return Database::fetchAll("
            SELECT *, DATEDIFF(due_date, CURDATE()) AS days_until
            FROM `m365_billing_records`
            WHERE license_id = ?
            ORDER BY due_date DESC
        ", [$licenseId]);
    }

    // ─── Single ───────────────────────────────────────────────────────────────

    public static function getById(int $id): ?array
    {
        return Database::fetchOne("
            SELECT br.*,
                   DATEDIFF(br.due_date, CURDATE()) AS days_until,
                   l.billing_interval, l.billing_days, l.remind_days, l.plan, l.client_id,
                   c.name AS client_name
            FROM `m365_billing_records` br
            JOIN `m365_licenses` l ON l.id = br.license_id
            JOIN `clients` c ON c.id = l.client_id
            WHERE br.id = ?
        ", [$id]);
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    /**
     * Create a billing record for the given license using license.next_billing_date as due_date.
     * Also advances next_billing_date on the license by one interval.
     */
    public static function createForLicense(array $license): int
    {
        $period = self::buildPeriod(
            $license['next_billing_date'],
            $license['billing_interval'],
            (int) $license['billing_days']
        );

        $id = Database::insert("
            INSERT INTO `m365_billing_records`
                (license_id, period_label, period_start, period_end, due_date, amount, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ", [
            $license['id'],
            $period['label'],
            $period['start'],
            $period['end'],
            $license['next_billing_date'],
            $license['amount'],
        ]);

        M365LicenseModel::advanceNextBillingDate(
            (int) $license['id'],
            $license['billing_interval'],
            (int) $license['billing_days'],
            $license['next_billing_date']
        );

        return $id;
    }

    /** Check whether a billing record already exists for a given license + due_date. */
    public static function existsForDueDate(int $licenseId, string $dueDate): bool
    {
        return Database::fetchOne(
            "SELECT id FROM `m365_billing_records` WHERE license_id = ? AND due_date = ?",
            [$licenseId, $dueDate]
        ) !== null;
    }

    /**
     * Auto-generate billing records for every active license whose next_billing_date
     * has arrived. Loops per license to catch up on multiple missed periods (stacking).
     * Safe to call on every page load — idempotent via existsForDueDate check.
     */
    public static function autoGenerateDue(): void
    {
        $licenses = M365LicenseModel::getDueBillingLicenses();

        foreach ($licenses as $lic) {
            $cap = 36; // safety cap — max 36 consecutive periods per license per call

            while ($cap-- > 0) {
                $dueDate = $lic['next_billing_date'] ?? null;

                if (!$dueDate || $dueDate > date('Y-m-d')) {
                    break;
                }

                if (!self::existsForDueDate((int) $lic['id'], $dueDate)) {
                    self::createForLicense($lic);
                }

                // Re-fetch to get the updated next_billing_date after advancing
                $lic = Database::fetchOne(
                    "SELECT * FROM `m365_licenses` WHERE id = ?",
                    [(int) $lic['id']]
                );

                if (!$lic) break;
            }
        }
    }

    public static function markPaid(int $id): void
    {
        Database::execute(
            "UPDATE `m365_billing_records` SET is_paid = 1, paid_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    /** Hide from dashboard widget (does NOT mark as paid). */
    public static function dismiss(int $id): void
    {
        Database::execute(
            "UPDATE `m365_billing_records` SET is_acknowledged = 1 WHERE id = ?",
            [$id]
        );
    }

    /** Undo a dismiss — record reappears on dashboard. */
    public static function restore(int $id): void
    {
        Database::execute(
            "UPDATE `m365_billing_records` SET is_acknowledged = 0 WHERE id = ?",
            [$id]
        );
    }

    // ─── Dashboard ────────────────────────────────────────────────────────────

    /**
     * Unpaid + unacknowledged records that are due within their license's remind_days
     * window (or already overdue). Used by dashboard widget.
     * Returns empty array if tables don't exist yet.
     */
    public static function getDueDashboard(): array
    {
        try {
            return Database::fetchAll("
                SELECT br.*,
                       DATEDIFF(br.due_date, CURDATE()) AS days_until,
                       l.plan, l.remind_days,
                       c.name AS client_name,
                       c.id   AS client_id_fk
                FROM `m365_billing_records` br
                JOIN `m365_licenses` l ON l.id = br.license_id
                JOIN `clients` c ON c.id = l.client_id
                WHERE br.is_paid = 0
                  AND br.is_acknowledged = 0
                  AND br.due_date <= DATE_ADD(CURDATE(), INTERVAL l.remind_days DAY)
                ORDER BY br.due_date ASC
            ");
        } catch (\Throwable) {
            return [];
        }
    }

    public static function countDueDashboard(): int
    {
        try {
            $row = Database::fetchOne("
                SELECT COUNT(*) AS n
                FROM `m365_billing_records` br
                JOIN `m365_licenses` l ON l.id = br.license_id
                WHERE br.is_paid = 0
                  AND br.is_acknowledged = 0
                  AND br.due_date <= DATE_ADD(CURDATE(), INTERVAL l.remind_days DAY)
            ");
            return (int) ($row['n'] ?? 0);
        } catch (\Throwable) {
            return 0;
        }
    }
}

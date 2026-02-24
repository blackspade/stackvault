<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class DnsTemplateModel
{
    // ─── Built-in template definitions ────────────────────────────────────────

    private const BUILTINS = [
        [
            'name'        => 'Basic Web Hosting',
            'description' => 'A, CNAME, and MX records for a standard web hosting setup.',
            'records'     => [
                ['record_type' => 'A',     'name' => '@',   'value' => '{ip}',            'ttl' => 3600, 'priority' => null, 'notes' => 'Root domain IPv4 address'],
                ['record_type' => 'CNAME', 'name' => 'www', 'value' => '{domain}.',        'ttl' => 3600, 'priority' => null, 'notes' => ''],
                ['record_type' => 'MX',    'name' => '@',   'value' => '{mail_server}.',   'ttl' => 3600, 'priority' => 10,   'notes' => 'Primary mail server'],
                ['record_type' => 'TXT',   'name' => '@',   'value' => '"v=spf1 {spf} ~all"', 'ttl' => 3600, 'priority' => null, 'notes' => 'SPF record'],
            ],
        ],
        [
            'name'        => 'Google Workspace Mail',
            'description' => 'MX records and SPF TXT for Google Workspace (G Suite) email hosting.',
            'records'     => [
                ['record_type' => 'MX',  'name' => '@', 'value' => 'aspmx.l.google.com.',      'ttl' => 3600, 'priority' => 1,    'notes' => ''],
                ['record_type' => 'MX',  'name' => '@', 'value' => 'alt1.aspmx.l.google.com.', 'ttl' => 3600, 'priority' => 5,    'notes' => ''],
                ['record_type' => 'MX',  'name' => '@', 'value' => 'alt2.aspmx.l.google.com.', 'ttl' => 3600, 'priority' => 5,    'notes' => ''],
                ['record_type' => 'MX',  'name' => '@', 'value' => 'alt3.aspmx.l.google.com.', 'ttl' => 3600, 'priority' => 10,   'notes' => ''],
                ['record_type' => 'MX',  'name' => '@', 'value' => 'alt4.aspmx.l.google.com.', 'ttl' => 3600, 'priority' => 10,   'notes' => ''],
                ['record_type' => 'TXT', 'name' => '@', 'value' => '"v=spf1 include:_spf.google.com ~all"', 'ttl' => 3600, 'priority' => null, 'notes' => 'SPF for Google Workspace'],
            ],
        ],
        [
            'name'        => 'Microsoft 365 Mail',
            'description' => 'MX, CNAME Autodiscover, and SPF records for Microsoft 365.',
            'records'     => [
                ['record_type' => 'MX',    'name' => '@',           'value' => '{domain}.mail.protection.outlook.com.',           'ttl' => 3600, 'priority' => 0,    'notes' => ''],
                ['record_type' => 'CNAME', 'name' => 'autodiscover', 'value' => 'autodiscover.outlook.com.',                      'ttl' => 3600, 'priority' => null, 'notes' => ''],
                ['record_type' => 'TXT',   'name' => '@',           'value' => '"v=spf1 include:spf.protection.outlook.com -all"', 'ttl' => 3600, 'priority' => null, 'notes' => 'SPF for Microsoft 365'],
            ],
        ],
        [
            'name'        => 'Zoho Mail',
            'description' => 'MX and SPF records for Zoho Mail hosting.',
            'records'     => [
                ['record_type' => 'MX',  'name' => '@', 'value' => 'mx.zoho.com.',  'ttl' => 3600, 'priority' => 10,   'notes' => ''],
                ['record_type' => 'MX',  'name' => '@', 'value' => 'mx2.zoho.com.', 'ttl' => 3600, 'priority' => 20,   'notes' => ''],
                ['record_type' => 'MX',  'name' => '@', 'value' => 'mx3.zoho.com.', 'ttl' => 3600, 'priority' => 50,   'notes' => ''],
                ['record_type' => 'TXT', 'name' => '@', 'value' => '"v=spf1 include:zoho.com ~all"', 'ttl' => 3600, 'priority' => null, 'notes' => 'SPF for Zoho Mail'],
            ],
        ],
        [
            'name'        => 'CAA — Let\'s Encrypt Only',
            'description' => 'CAA record restricting certificate issuance to Let\'s Encrypt only.',
            'records'     => [
                ['record_type' => 'CAA', 'name' => '@', 'value' => '0 issue "letsencrypt.org"', 'ttl' => 3600, 'priority' => 0, 'notes' => 'Restrict SSL issuance to Let\'s Encrypt'],
            ],
        ],
    ];

    // ─── Schema auto-install ──────────────────────────────────────────────────

    public static function ensureSchema(): void
    {
        Database::execute("
            CREATE TABLE IF NOT EXISTS `dns_templates` (
                `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                `name`        VARCHAR(120)    NOT NULL,
                `description` TEXT            NULL,
                `is_builtin`  TINYINT(1)      NOT NULL DEFAULT 0,
                `sort_order`  SMALLINT        NOT NULL DEFAULT 0,
                `created_at`  DATETIME        NOT NULL,
                `updated_at`  DATETIME        NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        Database::execute("
            CREATE TABLE IF NOT EXISTS `dns_template_records` (
                `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                `template_id` INT UNSIGNED    NOT NULL,
                `record_type` VARCHAR(10)     NOT NULL,
                `name`        VARCHAR(255)    NOT NULL,
                `value`       TEXT            NOT NULL,
                `ttl`         INT             NOT NULL DEFAULT 3600,
                `priority`    SMALLINT UNSIGNED NULL,
                `notes`       TEXT            NULL,
                `sort_order`  SMALLINT        NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                KEY `idx_dtr_template` (`template_id`),
                CONSTRAINT `fk_dtr_template`
                    FOREIGN KEY (`template_id`) REFERENCES `dns_templates` (`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    // ─── Built-in seed ────────────────────────────────────────────────────────

    public static function seedBuiltins(): void
    {
        foreach (self::BUILTINS as $sortOrder => $tpl) {
            $existing = Database::fetchOne(
                "SELECT id FROM `dns_templates` WHERE `name` = ? AND `is_builtin` = 1",
                [$tpl['name']]
            );

            if ($existing) {
                continue;
            }

            $id = Database::insert(
                "INSERT INTO `dns_templates` (name, description, is_builtin, sort_order, created_at, updated_at)
                 VALUES (?, ?, 1, ?, NOW(), NOW())",
                [$tpl['name'], $tpl['description'], $sortOrder]
            );

            foreach ($tpl['records'] as $j => $rec) {
                Database::execute(
                    "INSERT INTO `dns_template_records`
                        (template_id, record_type, name, value, ttl, priority, notes, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $id,
                        $rec['record_type'],
                        $rec['name'],
                        $rec['value'],
                        $rec['ttl'],
                        $rec['priority'],
                        $rec['notes'] !== '' ? $rec['notes'] : null,
                        $j,
                    ]
                );
            }
        }
    }

    // ─── Queries ──────────────────────────────────────────────────────────────

    public static function getAll(): array
    {
        return Database::fetchAll("
            SELECT t.*, COUNT(r.id) AS record_count
            FROM `dns_templates` t
            LEFT JOIN `dns_template_records` r ON r.template_id = t.id
            GROUP BY t.id
            ORDER BY t.is_builtin DESC, t.sort_order ASC, t.name ASC
        ");
    }

    public static function getById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM `dns_templates` WHERE id = ?",
            [$id]
        );
    }

    public static function getRecords(int $templateId): array
    {
        return Database::fetchAll(
            "SELECT * FROM `dns_template_records`
             WHERE template_id = ?
             ORDER BY sort_order ASC, id ASC",
            [$templateId]
        );
    }

    /** Returns all templates with their records embedded (used in the apply form). */
    public static function getAllWithRecords(): array
    {
        $templates = self::getAll();
        foreach ($templates as &$tpl) {
            $tpl['records'] = self::getRecords((int) $tpl['id']);
        }
        return $templates;
    }

    // ─── Write ────────────────────────────────────────────────────────────────

    public static function create(string $name, ?string $description, array $records): int
    {
        $id = Database::insert(
            "INSERT INTO `dns_templates` (name, description, is_builtin, sort_order, created_at, updated_at)
             VALUES (?, ?, 0, 999, NOW(), NOW())",
            [$name, $description]
        );

        self::replaceRecords($id, $records);
        return $id;
    }

    public static function update(int $id, string $name, ?string $description, array $records): void
    {
        Database::execute(
            "UPDATE `dns_templates` SET name = ?, description = ?, updated_at = NOW() WHERE id = ?",
            [$name, $description, $id]
        );

        self::replaceRecords($id, $records);
    }

    private static function replaceRecords(int $templateId, array $records): void
    {
        Database::execute(
            "DELETE FROM `dns_template_records` WHERE template_id = ?",
            [$templateId]
        );

        foreach ($records as $i => $rec) {
            $priority = isset($rec['priority']) && $rec['priority'] !== '' ? (int) $rec['priority'] : null;
            $notes    = isset($rec['notes']) && trim((string) $rec['notes']) !== '' ? trim((string) $rec['notes']) : null;

            Database::execute(
                "INSERT INTO `dns_template_records`
                    (template_id, record_type, name, value, ttl, priority, notes, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $templateId,
                    strtoupper(trim((string) ($rec['record_type'] ?? 'A'))),
                    trim((string) ($rec['name']  ?? '')),
                    trim((string) ($rec['value'] ?? '')),
                    (int) ($rec['ttl'] ?: 3600),
                    $priority,
                    $notes,
                    $i,
                ]
            );
        }
    }

    public static function delete(int $id): void
    {
        Database::execute(
            "DELETE FROM `dns_templates` WHERE id = ? AND is_builtin = 0",
            [$id]
        );
    }

    // ─── Variable helpers ──────────────────────────────────────────────────────

    /**
     * Scan a set of template records for {placeholder} variables.
     * Returns unique variable names excluding 'domain' (always auto-filled).
     */
    public static function detectVariables(array $records): array
    {
        $vars = [];
        foreach ($records as $rec) {
            preg_match_all('/\{(\w+)\}/', ($rec['name'] ?? '') . ' ' . ($rec['value'] ?? ''), $m);
            foreach ($m[1] as $v) {
                if ($v !== 'domain') {
                    $vars[$v] = true;
                }
            }
        }
        return array_keys($vars);
    }

    /**
     * Substitute {variable} placeholders in a record's name and value fields.
     * $vars = ['domain' => 'example.com', 'ip' => '1.2.3.4', ...]
     */
    public static function substituteVars(array $record, array $vars): array
    {
        $search  = array_map(static fn($k) => '{' . $k . '}', array_keys($vars));
        $replace = array_values($vars);

        $record['name']  = str_replace($search, $replace, $record['name']);
        $record['value'] = str_replace($search, $replace, $record['value']);

        return $record;
    }
}

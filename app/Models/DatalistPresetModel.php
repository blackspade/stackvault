<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class DatalistPresetModel
{
    /** Human-readable labels for each group (shown in Settings UI). */
    public const GROUP_LABELS = [
        'server_os'       => 'Server OS Versions',
        'server_provider' => 'Server Providers',
        'app_deployment'  => 'App Deployment Methods',
    ];

    /** Built-in defaults per group — these are seeded and cannot be deleted. */
    public const DEFAULTS = [
        'server_os' => [
            'Ubuntu 24.04 LTS',
            'Ubuntu 22.04 LTS',
            'Ubuntu 20.04 LTS',
            'Debian 12',
            'Debian 11',
            'CentOS Stream 9',
            'AlmaLinux 9',
            'Rocky Linux 9',
            'Windows Server 2022',
            'Windows Server 2019',
        ],
        'server_provider' => [
            'Hetzner',
            'DigitalOcean',
            'Vultr',
            'Linode',
            'AWS',
            'Google Cloud',
            'Microsoft Azure',
            'OVH',
            'Contabo',
            'Cloudflare',
            'Dedicated (Self-hosted)',
        ],
        'app_deployment' => [
            'Cloudron',
            'Docker Compose',
            'Docker',
            'Git',
            'Manual',
            'CI/CD',
            'FTP / SFTP',
        ],
    ];

    /** Idempotent bootstrap: create table + seed defaults once per request. */
    public static function init(): void
    {
        static $done = false;
        if (!$done) {
            self::ensureSchema();
            self::seedDefaults();
            $done = true;
        }
    }

    public static function ensureSchema(): void
    {
        Database::execute("
            CREATE TABLE IF NOT EXISTS `datalist_presets` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `group`      VARCHAR(50)  NOT NULL,
                `value`      VARCHAR(255) NOT NULL,
                `is_default` TINYINT(1)  NOT NULL DEFAULT 0,
                `sort_order` INT         NOT NULL DEFAULT 0,
                UNIQUE KEY `uq_group_value` (`group`(50), `value`(255)),
                INDEX `idx_group` (`group`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /** Idempotent seed of all built-in defaults. */
    public static function seedDefaults(): void
    {
        foreach (self::DEFAULTS as $group => $values) {
            foreach ($values as $i => $value) {
                Database::execute(
                    "INSERT IGNORE INTO `datalist_presets` (`group`, `value`, `is_default`, `sort_order`)
                     VALUES (?, ?, 1, ?)",
                    [$group, $value, $i]
                );
            }
        }
    }

    /** All presets for a group, defaults first then custom alphabetically. */
    public static function getByGroup(string $group): array
    {
        return Database::fetchAll(
            "SELECT * FROM `datalist_presets`
             WHERE `group` = ?
             ORDER BY `is_default` DESC, `sort_order` ASC, `value` ASC",
            [$group]
        );
    }

    /** All groups as [ group => [ rows ] ] — used in Settings view. */
    public static function getAllGroups(): array
    {
        $rows   = Database::fetchAll(
            "SELECT * FROM `datalist_presets` ORDER BY `group`, `is_default` DESC, `sort_order` ASC, `value` ASC"
        );
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['group']][] = $row;
        }
        return $groups;
    }

    /** Values only for a group — used to populate datalist options. */
    public static function getValues(string $group): array
    {
        $rows = self::getByGroup($group);
        return array_column($rows, 'value');
    }

    public static function add(string $group, string $value): bool
    {
        if (!array_key_exists($group, self::GROUP_LABELS)) {
            return false;
        }
        $value = trim($value);
        if ($value === '' || mb_strlen($value) > 255) {
            return false;
        }

        $affected = Database::execute(
            "INSERT IGNORE INTO `datalist_presets` (`group`, `value`, `is_default`, `sort_order`)
             VALUES (?, ?, 0, 999)",
            [$group, $value]
        );

        return $affected > 0;
    }

    /** Delete a custom preset (is_default=0 only). */
    public static function delete(int $id): bool
    {
        $row = Database::fetchOne(
            "SELECT id, is_default FROM `datalist_presets` WHERE id = ?", [$id]
        );
        if (!$row || (int) $row['is_default'] === 1) {
            return false;
        }
        Database::execute("DELETE FROM `datalist_presets` WHERE id = ? AND is_default = 0", [$id]);
        return true;
    }
}

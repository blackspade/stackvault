<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class SettingsModel
{
    // ─── Key/value settings ───────────────────────────────────────────────────

    public static function get(string $key, string $default = ''): string
    {
        $row = Database::fetchOne(
            "SELECT setting_value FROM `settings` WHERE setting_key = ? LIMIT 1",
            [$key]
        );
        return $row !== null ? (string) ($row['setting_value'] ?? $default) : $default;
    }

    public static function set(string $key, string $value, string $group = 'general'): void
    {
        Database::execute(
            "INSERT INTO `settings` (setting_key, setting_value, setting_group, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
            [$key, $value, $group]
        );
    }

    // ─── Vault key re-encryption ──────────────────────────────────────────────

    /**
     * Encrypt a raw vault key under a new password.
     * Uses the same format as the setup wizard and AuthService::decryptVaultKey():
     *   base64( salt[16] | iv[12] | gcm_tag[16] | ciphertext[32] )
     */
    public static function encryptVaultKey(string $rawKey, string $password): string
    {
        $salt = random_bytes(16);
        $iv   = random_bytes(12);

        $kek = hash_pbkdf2('sha256', $password, $salt, 100_000, 32, true);

        $tag        = '';
        $ciphertext = openssl_encrypt(
            $rawKey, 'aes-256-gcm', $kek,
            OPENSSL_RAW_DATA, $iv, $tag, '', 16
        );

        return base64_encode($salt . $iv . $tag . $ciphertext);
    }

    // ─── Login whitelist ──────────────────────────────────────────────────────

    /** Create the login_whitelist table if it doesn't exist (lazy migrate). */
    public static function migrate(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        Database::execute("
            CREATE TABLE IF NOT EXISTS `login_whitelist` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `ip_address` VARCHAR(45)  NOT NULL,
                `label`      VARCHAR(255) DEFAULT NULL,
                `created_at` DATETIME    NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_whitelist_ip` (`ip_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public static function isWhitelistEnabled(): bool
    {
        return self::get('whitelist_enabled', '0') === '1';
    }

    public static function setWhitelistEnabled(bool $enabled): void
    {
        self::set('whitelist_enabled', $enabled ? '1' : '0', 'security');
    }

    public static function getWhitelistIps(): array
    {
        self::migrate();
        return Database::fetchAll(
            "SELECT id, ip_address, label, created_at
             FROM `login_whitelist`
             ORDER BY created_at ASC"
        );
    }

    /**
     * Add an IP to the whitelist.
     * Returns false if the IP is already whitelisted.
     */
    public static function addToWhitelist(string $ip, string $label = ''): bool
    {
        self::migrate();
        $exists = Database::fetchOne(
            "SELECT id FROM `login_whitelist` WHERE ip_address = ? LIMIT 1",
            [$ip]
        );
        if ($exists) {
            return false;
        }

        Database::insert(
            "INSERT INTO `login_whitelist` (ip_address, label, created_at) VALUES (?, ?, NOW())",
            [$ip, $label !== '' ? $label : null]
        );
        return true;
    }

    public static function removeFromWhitelist(int $id): void
    {
        self::migrate();
        Database::execute("DELETE FROM `login_whitelist` WHERE id = ?", [$id]);
    }

    public static function isIpWhitelisted(string $ip): bool
    {
        self::migrate();
        return Database::fetchOne(
            "SELECT id FROM `login_whitelist` WHERE ip_address = ? LIMIT 1",
            [$ip]
        ) !== null;
    }
}

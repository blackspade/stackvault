<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Manages the totp_remember_tokens table used by the 2FA "remember this device" feature.
 *
 * Protocol:
 *  - On successful 2FA with "remember me" checked: issue() sets a 15-day cookie.
 *  - On login with valid 2FA-enabled account: validate() checks the cookie and slides the expiry.
 *  - On logout: revokeCurrentCookie() deletes the specific token for this browser.
 *  - On password change / 2FA disable: revoke() wipes all tokens for the user.
 */
class TotpRememberModel
{
    private const COOKIE_NAME = 'sv_2fa_rem';
    private const DAYS        = 15;

    // ─── Schema ───────────────────────────────────────────────────────────────

    public static function ensureSchema(): void
    {
        Database::execute("
            CREATE TABLE IF NOT EXISTS `totp_remember_tokens` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id`    INT UNSIGNED NOT NULL,
                `token_hash` VARCHAR(64)  NOT NULL,
                `expires_at` DATETIME     NOT NULL,
                `created_at` DATETIME     NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_token` (`token_hash`),
                KEY `idx_user_expires` (`user_id`, `expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // ─── Issue ────────────────────────────────────────────────────────────────

    /**
     * Generate a new remember token for $userId, persist its hash, and set the cookie.
     */
    public static function issue(int $userId): void
    {
        self::ensureSchema();

        $raw     = bin2hex(random_bytes(32));          // 64-char hex token
        $hash    = hash('sha256', $raw);
        $expires = time() + (self::DAYS * 86400);

        Database::execute(
            "INSERT INTO `totp_remember_tokens` (user_id, token_hash, expires_at, created_at)
             VALUES (?, ?, FROM_UNIXTIME(?), NOW())",
            [$userId, $hash, $expires]
        );

        self::setCookie($raw, $expires);
    }

    // ─── Validate ─────────────────────────────────────────────────────────────

    /**
     * Check the remember cookie. Returns the user_id it belongs to, or null.
     * On a valid hit the expiry slides forward by 15 days (rolling window).
     */
    public static function validate(): ?int
    {
        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if ($raw === null) {
            return null;
        }

        try {
            self::ensureSchema();
        } catch (\Throwable) {
            return null;
        }

        $hash = hash('sha256', $raw);
        $row  = Database::fetchOne(
            "SELECT id, user_id FROM `totp_remember_tokens`
             WHERE token_hash = ? AND expires_at > NOW()
             LIMIT 1",
            [$hash]
        );

        if ($row === null) {
            return null;
        }

        // Slide the window
        $newExpires = time() + (self::DAYS * 86400);
        Database::execute(
            "UPDATE `totp_remember_tokens` SET expires_at = FROM_UNIXTIME(?) WHERE id = ?",
            [$newExpires, (int) $row['id']]
        );
        self::setCookie($raw, $newExpires);

        return (int) $row['user_id'];
    }

    // ─── Revoke ───────────────────────────────────────────────────────────────

    /**
     * Delete only the token matching the current browser cookie (used on logout).
     */
    public static function revokeCurrentCookie(): void
    {
        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if ($raw !== null) {
            try {
                self::ensureSchema();
                Database::execute(
                    "DELETE FROM `totp_remember_tokens` WHERE token_hash = ?",
                    [hash('sha256', $raw)]
                );
            } catch (\Throwable) {}
        }
        self::setCookie('', time() - 3600);
    }

    /**
     * Delete all tokens for a user (call on password change or 2FA disable).
     */
    public static function revokeAll(int $userId): void
    {
        try {
            self::ensureSchema();
            Database::execute(
                "DELETE FROM `totp_remember_tokens` WHERE user_id = ?",
                [$userId]
            );
        } catch (\Throwable) {}
        self::setCookie('', time() - 3600);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static function setCookie(string $value, int $expires): void
    {
        setcookie(self::COOKIE_NAME, $value, [
            'expires'  => $expires,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }
}

<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class UserModel
{
    // ─── Lookups ──────────────────────────────────────────────────────────────

    public static function findByUsername(string $username): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1",
            [$username]
        );
    }

    public static function findById(int $id): ?array
    {
        return Database::fetchOne(
            "SELECT * FROM users WHERE id = ? LIMIT 1",
            [$id]
        );
    }

    // ─── Lockout helpers ──────────────────────────────────────────────────────

    /**
     * True if the user's locked_until is in the future.
     */
    public static function isLocked(array $user): bool
    {
        if (empty($user['locked_until'])) {
            return false;
        }
        return strtotime($user['locked_until']) > time();
    }

    /**
     * Minutes remaining on a lockout (rounded up), or 0 if not locked.
     */
    public static function lockMinutesRemaining(array $user): int
    {
        if (!self::isLocked($user)) {
            return 0;
        }
        return (int) ceil((strtotime($user['locked_until']) - time()) / 60);
    }

    // ─── Write operations ─────────────────────────────────────────────────────

    /**
     * Increment failed_login_attempts by 1 and return the new count.
     */
    public static function incrementFailedAttempts(int $userId): int
    {
        Database::execute(
            "UPDATE users
             SET failed_login_attempts = failed_login_attempts + 1,
                 updated_at = NOW()
             WHERE id = ?",
            [$userId]
        );

        $row = self::findById($userId);
        return (int) ($row['failed_login_attempts'] ?? 0);
    }

    /**
     * Lock the account for $minutes minutes from now.
     */
    public static function lock(int $userId, int $minutes = 15): void
    {
        Database::execute(
            "UPDATE users
             SET locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                 updated_at = NOW()
             WHERE id = ?",
            [$minutes, $userId]
        );
    }

    /**
     * Record a successful login: clear failure counters, update last_login.
     */
    public static function recordSuccess(int $userId, string $ip): void
    {
        Database::execute(
            "UPDATE users
             SET last_login_at         = NOW(),
                 last_login_ip         = ?,
                 failed_login_attempts = 0,
                 locked_until          = NULL,
                 updated_at            = NOW()
             WHERE id = ?",
            [$ip, $userId]
        );
    }

    // ─── Profile / settings updates ───────────────────────────────────────────

    public static function updateProfile(int $id, string $username, string $email): void
    {
        Database::execute(
            "UPDATE `users` SET username = ?, email = ?, updated_at = NOW() WHERE id = ?",
            [$username, $email, $id]
        );
    }

    public static function updatePassword(int $id, string $hash): void
    {
        Database::execute(
            "UPDATE `users` SET password_hash = ?, updated_at = NOW() WHERE id = ?",
            [$hash, $id]
        );
    }

    public static function updateVaultKey(int $id, string $encryptedKey, string $passwordHash): void
    {
        Database::execute(
            "UPDATE `users`
             SET vault_key_encrypted = ?, vault_password_hash = ?, updated_at = NOW()
             WHERE id = ?",
            [$encryptedKey, $passwordHash, $id]
        );
    }

    public static function usernameExists(string $username, int $excludeId = 0): bool
    {
        return Database::fetchOne(
            "SELECT id FROM `users` WHERE username = ? AND id != ? LIMIT 1",
            [$username, $excludeId]
        ) !== null;
    }

    public static function emailExists(string $email, int $excludeId = 0): bool
    {
        return Database::fetchOne(
            "SELECT id FROM `users` WHERE email = ? AND id != ? LIMIT 1",
            [$email, $excludeId]
        ) !== null;
    }

    // ─── TOTP / 2FA ───────────────────────────────────────────────────────────

    public static function updateTotp(int $id, string $encryptedSecret, bool $enabled): void
    {
        Database::execute(
            "UPDATE `users`
             SET totp_secret = ?, totp_enabled = ?, updated_at = NOW()
             WHERE id = ?",
            [$encryptedSecret, $enabled ? 1 : 0, $id]
        );
    }

    public static function clearTotp(int $id): void
    {
        Database::execute(
            "UPDATE `users`
             SET totp_secret = NULL, totp_enabled = 0, updated_at = NOW()
             WHERE id = ?",
            [$id]
        );
    }
}

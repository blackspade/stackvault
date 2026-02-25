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

    // ─── Multi-user schema migration ──────────────────────────────────────────

    /**
     * Lazy-add must_setup_2fa column (idempotent).
     */
    public static function ensureNewColumns(): void
    {
        try {
            Database::execute(
                "ALTER TABLE `users` ADD COLUMN `must_setup_2fa` TINYINT(1) NOT NULL DEFAULT 0"
            );
        } catch (\Throwable) {
            // Column already exists — ignore duplicate column error
        }
    }

    // ─── Multi-user management ────────────────────────────────────────────────

    /** Return all active users ordered by id. */
    public static function getAll(): array
    {
        return Database::fetchAll(
            "SELECT id, username, email, role, totp_enabled, must_setup_2fa,
                    last_login_at, created_at
             FROM `users`
             WHERE is_active = 1
             ORDER BY id ASC"
        ) ?: [];
    }

    /** Count of active users. */
    public static function count(): int
    {
        $row = Database::fetchOne("SELECT COUNT(*) AS n FROM `users` WHERE is_active = 1");
        return (int) ($row['n'] ?? 0);
    }

    /**
     * Create a new user. Returns the new user's id.
     *
     * @param string $username
     * @param string $email
     * @param string $passwordHash      Argon2id hash of login password
     * @param string $vaultKeyEncrypted Vault key re-encrypted with user's vault password
     * @param string $vaultPasswordHash Argon2id hash of vault password
     * @param string $role
     * @param bool   $mustSetup2fa
     */
    public static function create(
        string $username,
        string $email,
        string $passwordHash,
        string $vaultKeyEncrypted,
        string $vaultPasswordHash,
        string $role = 'admin',
        bool   $mustSetup2fa = true
    ): int {
        Database::execute(
            "INSERT INTO `users`
                (username, email, password_hash, vault_key_encrypted, vault_password_hash,
                 role, must_setup_2fa, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())",
            [
                $username,
                $email,
                $passwordHash,
                $vaultKeyEncrypted,
                $vaultPasswordHash,
                $role,
                $mustSetup2fa ? 1 : 0,
            ]
        );
        $row = Database::fetchOne("SELECT LAST_INSERT_ID() AS id");
        return (int) ($row['id'] ?? 0);
    }

    /** Hard-delete a user by id. */
    public static function deleteById(int $id): void
    {
        Database::execute("DELETE FROM `users` WHERE id = ?", [$id]);
    }

    /** Set or clear the must_setup_2fa flag. Also updates session if it's the current user. */
    public static function setMustSetup2fa(int $id, bool $flag): void
    {
        Database::execute(
            "UPDATE `users` SET must_setup_2fa = ?, updated_at = NOW() WHERE id = ?",
            [$flag ? 1 : 0, $id]
        );
    }
}

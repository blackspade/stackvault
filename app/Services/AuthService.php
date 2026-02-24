<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\UserModel;

/**
 * AuthService
 *
 * Handles login and vault-unlock flows.
 *
 * Login flow (attempt):
 *   1. Username lookup (constant-time on miss)
 *   2. Account lockout check
 *   3. Login password verification  (Argon2id)
 *   4. Failed-attempt tracking and progressive lockout
 *   5. Activity logging
 *
 * The vault key is NOT decrypted at login — the user unlocks it separately
 * via unlockVault() when they need to view credentials.
 *
 * Vault key storage format (set by setup.php sv_encrypt_vault_key):
 *   base64( salt[16] | iv[12] | gcm_tag[16] | ciphertext[32] )  = 76 raw bytes
 */
class AuthService
{
    private const MAX_ATTEMPTS    = 5;
    private const LOCKOUT_MINUTES = 15;
    private const PBKDF2_ALGO     = 'sha256';
    private const PBKDF2_ITERS    = 100_000;
    private const PBKDF2_KEYLEN   = 32;

    // ─── Login ────────────────────────────────────────────────────────────────

    /**
     * Attempt to authenticate with username + login password only.
     * The vault key is not touched here.
     *
     * @return array{success:true, user:array}
     *            | array{success:false, error:string}
     */
    public static function attempt(
        string $username,
        string $loginPassword,
        string $ip,
        string $userAgent = ''
    ): array {
        // 1. Find user ─────────────────────────────────────────────────────────
        $user = UserModel::findByUsername($username);

        if (!$user) {
            // Still run a hash comparison to equalise response time
            // and prevent user-existence enumeration via timing.
            password_verify($loginPassword, self::dummyHash());
            return ['success' => false, 'error' => 'Invalid credentials.'];
        }

        $uid = (int) $user['id'];

        // 2. Lockout check ─────────────────────────────────────────────────────
        if (UserModel::isLocked($user)) {
            $mins = UserModel::lockMinutesRemaining($user);
            self::log($uid, 'login_blocked', 'user', $uid,
                "Login blocked — account locked ({$mins}m remaining)", $ip, $userAgent);
            return [
                'success' => false,
                'error'   => "Account locked. Try again in {$mins} minute(s).",
            ];
        }

        // 3. Login password (Argon2id) ──────────────────────────────────────────
        if (!password_verify($loginPassword, $user['password_hash'])) {
            return self::handleFailure($uid, 'wrong login password', $ip, $userAgent);
        }

        // 4. Success ───────────────────────────────────────────────────────────
        UserModel::recordSuccess($uid, $ip);
        self::log($uid, 'login', 'user', $uid, 'Successful login', $ip, $userAgent);

        return [
            'success' => true,
            'user'    => [
                'id'       => $uid,
                'username' => $user['username'],
                'email'    => $user['email'],
                'role'     => $user['role'],
            ],
        ];
    }

    // ─── Vault unlock ─────────────────────────────────────────────────────────

    /**
     * Verify the vault password and decrypt the vault key for the logged-in user.
     * On success, caller should store the raw key in $_SESSION['vault_key'].
     *
     * @return array{success:true,  vault_key:string}
     *            | array{success:false, error:string}
     */
    public static function unlockVault(
        int    $userId,
        string $vaultPassword,
        string $ip,
        string $userAgent = ''
    ): array {
        $user = UserModel::findById($userId);

        if (!$user) {
            return ['success' => false, 'error' => 'User not found.'];
        }

        // Check lockout (wrong vault passwords count against the same counter)
        if (UserModel::isLocked($user)) {
            $mins = UserModel::lockMinutesRemaining($user);
            return [
                'success' => false,
                'error'   => "Account locked. Try again in {$mins} minute(s).",
            ];
        }

        // Verify vault password hash (fast Argon2id pre-check)
        if (!empty($user['vault_password_hash'])
            && !password_verify($vaultPassword, $user['vault_password_hash'])
        ) {
            return self::handleFailure($userId, 'wrong vault password', $ip, $userAgent);
        }

        // Decrypt vault key (PBKDF2 + AES-256-GCM)
        if (empty($user['vault_key_encrypted'])) {
            self::log($userId, 'vault_error', 'user', $userId,
                'vault_key_encrypted is NULL — setup may be incomplete', $ip, $userAgent);
            return ['success' => false, 'error' => 'Vault not configured. Run setup again.'];
        }

        $vaultKey = self::decryptVaultKey($user['vault_key_encrypted'], $vaultPassword);

        if ($vaultKey === false) {
            self::log($userId, 'vault_decrypt_failed', 'user', $userId,
                'Vault key decryption failed despite correct password hash', $ip, $userAgent);
            return ['success' => false, 'error' => 'Vault decryption failed.'];
        }

        self::log($userId, 'vault_unlocked', 'user', $userId,
            'Vault unlocked', $ip, $userAgent);

        return ['success' => true, 'vault_key' => $vaultKey];
    }

    // ─── Vault key decryption ─────────────────────────────────────────────────

    /**
     * Decrypt the vault key stored in users.vault_key_encrypted.
     *
     * Format: base64( salt[16] | iv[12] | gcm_tag[16] | ciphertext[32] )
     * Key derivation: PBKDF2-SHA256(password, salt, 100k iterations, 32 bytes)
     *
     * Returns the raw 32-byte vault key, or FALSE on wrong password / corrupt data.
     */
    public static function decryptVaultKey(string $encoded, string $password): string|false
    {
        $raw = base64_decode($encoded, strict: true);

        // Minimum 76 bytes: salt(16) + iv(12) + tag(16) + ciphertext(32)
        if ($raw === false || strlen($raw) < 76) {
            return false;
        }

        $salt   = substr($raw, 0, 16);
        $iv     = substr($raw, 16, 12);
        $tag    = substr($raw, 28, 16);
        $cipher = substr($raw, 44);       // 32 bytes

        $key = hash_pbkdf2(
            self::PBKDF2_ALGO,
            $password,
            $salt,
            self::PBKDF2_ITERS,
            self::PBKDF2_KEYLEN,
            true
        );

        // Returns the raw vault key, or false if GCM tag verification fails
        return openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    }

    // ─── Activity logging ─────────────────────────────────────────────────────

    /**
     * Insert a row into activity_logs.
     * Silently absorbs exceptions so auth flow is never interrupted by log failures.
     */
    public static function log(
        ?int   $userId,
        string $action,
        string $entityType,
        int    $entityId,
        string $description,
        string $ip,
        string $userAgent = ''
    ): void {
        try {
            Database::insert(
                "INSERT INTO activity_logs
                    (user_id, action, entity_type, entity_id, description, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $userId,
                    $action,
                    $entityType,
                    $entityId,
                    $description,
                    substr($ip, 0, 45),
                    substr($userAgent, 0, 500),
                ]
            );
        } catch (\Throwable) {
            // Never let logging break the auth flow
        }
    }

    // ─── Internal helpers ─────────────────────────────────────────────────────

    /**
     * Increment the failure counter, lock if threshold reached, return error response.
     */
    private static function handleFailure(
        int    $uid,
        string $reason,
        string $ip,
        string $userAgent
    ): array {
        $attempts = UserModel::incrementFailedAttempts($uid);

        self::log($uid, 'login_failed', 'user', $uid,
            "Failed attempt ({$reason}) #{$attempts}", $ip, $userAgent);

        if ($attempts >= self::MAX_ATTEMPTS) {
            UserModel::lock($uid, self::LOCKOUT_MINUTES);
            self::log($uid, 'account_locked', 'user', $uid,
                "Account locked for " . self::LOCKOUT_MINUTES . " minutes after {$attempts} failed attempts",
                $ip, $userAgent);
            return [
                'success' => false,
                'error'   => 'Too many failed attempts. Account locked for '
                           . self::LOCKOUT_MINUTES . ' minutes.',
            ];
        }

        $remaining = self::MAX_ATTEMPTS - $attempts;
        return [
            'success' => false,
            'error'   => "Invalid credentials. {$remaining} attempt(s) remaining before lockout.",
        ];
    }

    /**
     * A pre-computed Argon2id hash of a dummy string used to equalise response
     * time when the username does not exist, preventing user enumeration via timing.
     */
    private static function dummyHash(): string
    {
        // This hash is for the string "dummy_no_user_found" — never changes
        return '$argon2id$v=19$m=65536,t=4,p=1$c2FsdHNhbHRzYWx0c2Fs$T4eTQBFWaT5XJNJYGajeMB4tNm2pFAOcXnVQGnpHJlo';
    }
}

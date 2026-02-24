<?php
declare(strict_types=1);

namespace App\Services;

/**
 * EncryptionService
 *
 * Handles AES-256-GCM encryption/decryption of credential fields
 * using the per-user vault key stored in the session.
 *
 * Storage format for encrypted values:
 *   base64( iv[12] | gcm_tag[16] | ciphertext[variable] )
 *
 * The vault key itself is a 32-byte random value decrypted at login
 * via AuthService::decryptVaultKey() and held in $_SESSION['vault_key'].
 */
class EncryptionService
{
    private const CIPHER     = 'aes-256-gcm';
    private const TAG_LENGTH = 16;
    private const IV_LENGTH  = 12;

    // ─── Core encrypt / decrypt ───────────────────────────────────────────────

    /**
     * Encrypt $plaintext with $vaultKey (32 raw bytes).
     * Returns a base64-encoded ciphertext string safe for DB storage.
     *
     * @throws \InvalidArgumentException  if key is not 32 bytes
     * @throws \RuntimeException          if OpenSSL fails
     */
    public static function encrypt(string $plaintext, string $vaultKey): string
    {
        if (strlen($vaultKey) !== 32) {
            throw new \InvalidArgumentException('Vault key must be exactly 32 bytes.');
        }

        $iv  = random_bytes(self::IV_LENGTH);
        $tag = '';
        $enc = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $vaultKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($enc === false) {
            throw new \RuntimeException('AES-256-GCM encryption failed.');
        }

        // iv[12] | tag[16] | ciphertext
        return base64_encode($iv . $tag . $enc);
    }

    /**
     * Decrypt a base64-encoded value produced by encrypt().
     * Returns the plaintext string, or FALSE if the key is wrong
     * or the data is corrupted (GCM authentication tag mismatch).
     */
    public static function decrypt(string $encoded, string $vaultKey): string|false
    {
        if (strlen($vaultKey) !== 32) {
            return false;
        }

        $raw = base64_decode($encoded, strict: true);
        // Minimum: IV(12) + TAG(16) + 1 byte of ciphertext = 29
        if ($raw === false || strlen($raw) < 29) {
            return false;
        }

        $iv     = substr($raw, 0, self::IV_LENGTH);
        $tag    = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $cipher = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);

        $result = openssl_decrypt(
            $cipher,
            self::CIPHER,
            $vaultKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $result; // false = wrong key or tampered data
    }

    // ─── Session-key convenience methods ─────────────────────────────────────

    /**
     * Encrypt using the vault key currently held in the session.
     *
     * @throws \RuntimeException  if the vault is locked (key not in session)
     */
    public static function encryptField(string $plaintext): string
    {
        $key = $_SESSION['vault_key'] ?? null;
        if (!$key || strlen($key) !== 32) {
            throw new \RuntimeException('Vault is locked — cannot encrypt field.');
        }
        return self::encrypt($plaintext, $key);
    }

    /**
     * Decrypt using the vault key currently held in the session.
     * Returns false if the vault is locked or decryption fails.
     */
    public static function decryptField(string $encoded): string|false
    {
        $key = $_SESSION['vault_key'] ?? null;
        if (!$key || strlen($key) !== 32) {
            return false;
        }
        return self::decrypt($encoded, $key);
    }

    /**
     * Decrypt and return a displayable value, or a placeholder if unavailable.
     */
    public static function decryptOrMask(string $encoded, string $mask = '••••••••'): string
    {
        $result = self::decryptField($encoded);
        return ($result !== false) ? $result : $mask;
    }
}

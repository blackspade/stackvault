<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

/**
 * TotpService — pure-PHP RFC 6238 TOTP implementation
 *
 * No external dependencies. Uses:
 *   - hash_hmac('sha1', ...) for HOTP
 *   - openssl_encrypt/decrypt (AES-256-GCM) to protect the stored secret
 *   - APP_KEY from .env as the envelope-encryption key
 */
class TotpService
{
    private const DIGITS       = 6;
    private const PERIOD       = 30;
    private const SECRET_BYTES = 20;     // 160 bits → 32 base32 chars
    private const WINDOW       = 1;      // allow ±1 time-step (30s each side)
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    // ─── DB migration ─────────────────────────────────────────────────────────

    /**
     * Lazily add totp_secret + totp_enabled columns to the users table.
     * Safe to call on every request; skips if columns already exist.
     */
    public static function migrate(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $hasCol = Database::fetchOne("
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'users'
              AND COLUMN_NAME  = 'totp_secret'
        ");

        if (!$hasCol) {
            Database::execute(
                "ALTER TABLE `users`
                 ADD COLUMN `totp_secret`  VARCHAR(128) DEFAULT NULL AFTER `vault_password_hash`,
                 ADD COLUMN `totp_enabled` TINYINT(1)  NOT NULL DEFAULT 0 AFTER `totp_secret`"
            );
        }
    }

    // ─── Secret management ────────────────────────────────────────────────────

    /** Generate a new random TOTP secret (base32-encoded, 32 chars). */
    public static function generateSecret(): string
    {
        return self::base32Encode(random_bytes(self::SECRET_BYTES));
    }

    /** Format a base32 secret in groups of 4 for readability (AAAA BBBB …). */
    public static function formatSecret(string $secret): string
    {
        return implode(' ', str_split($secret, 4));
    }

    /** Build the otpauth:// URI for QR code generators and authenticator apps. */
    public static function buildUri(string $secret, string $account, string $issuer): string
    {
        return 'otpauth://totp/'
            . rawurlencode($issuer . ':' . $account)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer)
            . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }

    // ─── Verification ─────────────────────────────────────────────────────────

    /**
     * Verify a 6-digit TOTP code against a base32-encoded secret.
     * Allows ±WINDOW time-steps (~30s each) to accommodate clock drift.
     */
    public static function verify(string $secret, string $code, int $window = self::WINDOW): bool
    {
        if (strlen($code) !== self::DIGITS || !ctype_digit($code)) {
            return false;
        }

        $rawSecret = self::base32Decode(strtoupper($secret));
        if ($rawSecret === false || $rawSecret === '') {
            return false;
        }

        $timeStep = (int) floor(time() / self::PERIOD);

        for ($offset = -$window; $offset <= $window; $offset++) {
            if (hash_equals(self::hotp($rawSecret, $timeStep + $offset), $code)) {
                return true;
            }
        }

        return false;
    }

    // ─── Secret encryption (APP_KEY envelope) ─────────────────────────────────

    /**
     * Encrypt a base32 TOTP secret using AES-256-GCM.
     * Key is derived from APP_KEY in .env so it survives server restarts.
     * Stored format: base64( iv[12] | tag[16] | ciphertext )
     */
    public static function encryptSecret(string $secret): string
    {
        $key = self::deriveKey();
        $iv  = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $secret, 'aes-256-gcm', $key,
            OPENSSL_RAW_DATA, $iv, $tag, '', 16
        );

        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt a previously-encrypted TOTP secret.
     * Returns the plaintext base32 secret, or false on failure.
     */
    public static function decryptSecret(string $encoded): string|false
    {
        $raw = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < 28) {
            return false;
        }

        $iv         = substr($raw, 0,  12);
        $tag        = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);

        $result = openssl_decrypt(
            $ciphertext, 'aes-256-gcm', self::deriveKey(),
            OPENSSL_RAW_DATA, $iv, $tag
        );

        return $result;  // string or false
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /** HOTP(key, counter) → 6-digit string (RFC 4226). */
    private static function hotp(string $key, int $counter): string
    {
        $data = pack('J', $counter);                          // big-endian 64-bit
        $hash = hash_hmac('sha1', $data, $key, true);        // 20-byte binary

        $offset = ord($hash[19]) & 0x0F;                     // dynamic truncation

        $otp = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8)  |
             (ord($hash[$offset + 3]) & 0xFF)
        ) % 1_000_000;

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** Base32-encode raw bytes (RFC 4648, no line breaks). */
    private static function base32Encode(string $data): string
    {
        $chars  = self::BASE32_CHARS;
        $result = '';
        $len    = strlen($data);

        for ($i = 0; $i < $len; $i += 5) {
            $b = [0, 0, 0, 0, 0];
            for ($j = 0; $j < 5 && ($i + $j) < $len; $j++) {
                $b[$j] = ord($data[$i + $j]);
            }

            $result .= $chars[($b[0] >> 3) & 31];
            $result .= $chars[(($b[0] << 2) | ($b[1] >> 6)) & 31];
            $result .= $chars[($b[1] >> 1) & 31];
            $result .= $chars[(($b[1] << 4) | ($b[2] >> 4)) & 31];
            $result .= $chars[(($b[2] << 1) | ($b[3] >> 7)) & 31];
            $result .= $chars[($b[3] >> 2) & 31];
            $result .= $chars[(($b[3] << 3) | ($b[4] >> 5)) & 31];
            $result .= $chars[$b[4] & 31];
        }

        // Pad to a multiple of 8 chars
        static $padMap = [0 => 0, 1 => 6, 2 => 4, 3 => 3, 4 => 1];
        $result .= str_repeat('=', $padMap[$len % 5]);

        return $result;
    }

    /** Base32-decode a string (RFC 4648). Returns raw bytes or false on invalid input. */
    private static function base32Decode(string $data): string|false
    {
        $chars  = self::BASE32_CHARS;
        $data   = rtrim(strtoupper($data), '=');
        $result = '';
        $bits   = 0;
        $value  = 0;

        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $pos = strpos($chars, $data[$i]);
            if ($pos === false) {
                return false;   // invalid character
            }

            $value = ($value << 5) | $pos;
            $bits += 5;

            if ($bits >= 8) {
                $result .= chr(($value >> ($bits - 8)) & 0xFF);
                $bits   -= 8;
            }
        }

        return $result;
    }

    /**
     * Derive a stable 32-byte AES key from APP_KEY.
     * Using HKDF-style derivation with a fixed info string so the
     * TOTP key is isolated from any other use of APP_KEY.
     */
    private static function deriveKey(): string
    {
        $appKey = Config::get('APP_KEY', '');
        return hash_hmac('sha256', 'totp-secret-v1', $appKey, true);
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\SettingsModel;
use App\Models\TotpRememberModel;
use App\Models\UserModel;
use App\Services\AuthService;
use App\Services\TotpService;

class AuthController extends Controller
{
    private const TOTP_PENDING_TTL = 300; // 5 minutes

    // ─── GET /login ───────────────────────────────────────────────────────────

    public function showLogin(): void
    {
        if (is_auth()) {
            $this->redirect('/dashboard');
        }

        // If mid-2FA, send back to the verification page
        if (!empty($_SESSION['2fa_pending'])) {
            $this->redirect('/login/2fa');
        }

        // ── Login whitelist check ─────────────────────────────────────────────
        $whitelisted = true;
        $clientIp    = $this->request->ip();
        try {
            if (SettingsModel::isWhitelistEnabled()) {
                $whitelisted = SettingsModel::isIpWhitelisted($clientIp);
            }
        } catch (\Throwable) {
            // DB unavailable — fail open so admins aren't locked out
        }

        $this->view('auth/login', [
            'title'        => 'Sign In',
            'timeout'      => $this->request->get('reason') === 'timeout',
            'errors'       => get_flash('error'),
            'old_username' => get_flash('old_username')[0] ?? '',
            'whitelisted'  => $whitelisted,
            'clientIp'     => $clientIp,
        ], 'auth');
    }

    // ─── POST /login ──────────────────────────────────────────────────────────

    public function login(): void
    {
        $this->validateCsrf();

        // Clear any stale 2FA pending state if the user submits a fresh login
        unset($_SESSION['2fa_pending']);

        $username      = trim((string) $this->request->post('username',       ''));
        $loginPassword =       (string) $this->request->post('login_password', '');
        $ip            = $this->request->ip();
        $ua            = $this->request->userAgent();

        // ── Whitelist guard ───────────────────────────────────────────────────
        try {
            if (SettingsModel::isWhitelistEnabled() && !SettingsModel::isIpWhitelisted($ip)) {
                flash('error', 'Access denied: your IP address is not authorized.');
                $this->redirect('/login');
            }
        } catch (\Throwable) {
            // DB unavailable — fail open
        }

        // ── Basic presence check ──────────────────────────────────────────────
        if ($username === '' || $loginPassword === '') {
            flash('error', 'Username and password are required.');
            if ($username !== '') {
                flash('old_username', $username);
            }
            $this->redirect('/login');
        }

        // ── Delegate to AuthService ───────────────────────────────────────────
        $result = AuthService::attempt($username, $loginPassword, $ip, $ua);

        if (!$result['success']) {
            flash('error', $result['error']);
            flash('old_username', $username);
            $this->redirect('/login');
        }

        // ── Check 2FA ─────────────────────────────────────────────────────────
        try {
            TotpService::migrate();
            UserModel::ensureNewColumns();
            $fullUser = UserModel::findById((int) $result['user']['id']);

            if (!empty($fullUser['totp_enabled']) && !empty($fullUser['totp_secret'])) {
                // Check for a valid remember-me cookie before requiring 2FA
                $rememberedUserId = TotpRememberModel::validate();
                if ($rememberedUserId === (int) $result['user']['id']) {
                    // Trusted device — skip 2FA, complete login directly
                    session_regenerate_id(true);
                    $_SESSION['user_id']       = $result['user']['id'];
                    $_SESSION['user']          = array_merge($result['user'], [
                        'must_setup_2fa' => (int) ($fullUser['must_setup_2fa'] ?? 0),
                    ]);
                    $_SESSION['last_activity'] = time();
                    $_SESSION['csrf_token']    = bin2hex(random_bytes(32));
                    AuthService::log(
                        (int) $result['user']['id'], 'login_2fa_remembered', 'user',
                        (int) $result['user']['id'], 'Login via trusted device (2FA remembered)',
                        $ip, $ua
                    );
                    $this->redirect('/dashboard');
                }

                // 2FA required — park a minimal pending state and redirect
                session_regenerate_id(true);
                $_SESSION['2fa_pending'] = [
                    'user'        => $result['user'],
                    'totp_secret' => $fullUser['totp_secret'],   // AES-encrypted
                    'at'          => time(),
                ];
                $this->redirect('/login/2fa');
            }
        } catch (\Throwable $e) {
            // Log the failure but never silently bypass 2FA
            error_log('[StackVault][2FA] Check failed for user ' . ($result['user']['id'] ?? '?') . ': ' . $e->getMessage());
            flash('error', 'A server error occurred during login. Please try again.');
            $this->redirect('/login');
        }

        // ── No 2FA — complete login normally ──────────────────────────────────
        session_regenerate_id(true);

        $_SESSION['user_id']       = $result['user']['id'];
        $_SESSION['user']          = $result['user'];
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token']    = bin2hex(random_bytes(32));

        $this->redirect('/dashboard');
    }

    // ─── GET /login/2fa ───────────────────────────────────────────────────────

    public function show2fa(): void
    {
        if (is_auth()) {
            $this->redirect('/dashboard');
        }

        $pending = $_SESSION['2fa_pending'] ?? null;

        if (!$pending || (time() - (int) ($pending['at'] ?? 0)) > self::TOTP_PENDING_TTL) {
            unset($_SESSION['2fa_pending']);
            flash('error', 'Session expired. Please sign in again.');
            $this->redirect('/login');
        }

        $this->view('auth/2fa', [
            'title'    => 'Two-Factor Authentication',
            'errors'   => get_flash('error'),
            'username' => $pending['user']['username'] ?? '',
        ], 'auth');
    }

    // ─── POST /login/2fa ──────────────────────────────────────────────────────

    public function verify2fa(): void
    {
        $this->validateCsrf();

        $pending = $_SESSION['2fa_pending'] ?? null;

        if (!$pending || (time() - (int) ($pending['at'] ?? 0)) > self::TOTP_PENDING_TTL) {
            unset($_SESSION['2fa_pending']);
            flash('error', 'Session expired. Please sign in again.');
            $this->redirect('/login');
        }

        $code = trim((string) $this->request->post('totp_code', ''));

        if (strlen($code) !== 6 || !ctype_digit($code)) {
            flash('error', 'Please enter a valid 6-digit code.');
            $this->redirect('/login/2fa');
        }

        // Decrypt stored secret and verify the code
        $encryptedSecret = $pending['totp_secret'] ?? '';
        $secret          = TotpService::decryptSecret($encryptedSecret);

        if ($secret === false || !TotpService::verify($secret, $code)) {
            flash('error', 'Incorrect code. Please try again.');
            $this->redirect('/login/2fa');
        }

        // ── 2FA passed — complete login ───────────────────────────────────────
        $user = $pending['user'];
        unset($_SESSION['2fa_pending']);

        session_regenerate_id(true);

        // Fetch fresh user row so must_setup_2fa is current
        $fullUser = UserModel::findById((int) $user['id']);

        $_SESSION['user_id']       = $user['id'];
        $_SESSION['user']          = array_merge($user, [
            'must_setup_2fa' => (int) ($fullUser['must_setup_2fa'] ?? 0),
        ]);
        $_SESSION['last_activity'] = time();
        $_SESSION['csrf_token']    = bin2hex(random_bytes(32));

        // Issue remember cookie if user opted in
        if ($this->request->post('remember_device') === '1') {
            try {
                TotpRememberModel::issue((int) $user['id']);
            } catch (\Throwable) {}
        }

        AuthService::log(
            (int) $user['id'],
            'login_2fa',
            'user',
            (int) $user['id'],
            'Successful login with 2FA',
            $this->request->ip(),
            $this->request->userAgent()
        );

        $this->redirect('/dashboard');
    }

    // ─── GET /logout ──────────────────────────────────────────────────────────

    public function logout(): void
    {
        // Log before destroying session data
        if (!empty($_SESSION['user_id'])) {
            AuthService::log(
                (int) $_SESSION['user_id'],
                'logout',
                'user',
                (int) $_SESSION['user_id'],
                'User logged out',
                $this->request->ip(),
                $this->request->userAgent()
            );
        }

        // Revoke the remember-me cookie for this browser only
        try {
            TotpRememberModel::revokeCurrentCookie();
        } catch (\Throwable) {}

        // Explicitly wipe the vault key from memory before session destruction
        if (isset($_SESSION['vault_key'])) {
            $_SESSION['vault_key'] = str_repeat("\x00", 32);
            unset($_SESSION['vault_key']);
        }

        session_unset();
        session_destroy();

        $this->redirect('/login');
    }
}

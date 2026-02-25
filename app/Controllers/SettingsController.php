<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Config;
use App\Models\ActivityLogModel;
use App\Models\DatalistPresetModel;
use App\Models\SettingsModel;
use App\Models\TotpRememberModel;
use App\Models\UserModel;
use App\Services\AuthService;
use App\Services\TotpService;

class SettingsController extends Controller
{
    // ─── GET /settings ────────────────────────────────────────────────────────

    public function index(): void
    {
        $userId = (int) $_SESSION['user_id'];
        $user   = UserModel::findById($userId);

        $activeTab = $this->request->get('tab', 'general');
        if (!in_array($activeTab, ['general', 'profile', 'vault', 'whitelist', '2fa', 'users', 'logs', 'export'], true)) {
            $activeTab = 'general';
        }

        SettingsModel::migrate();
        TotpService::migrate();
        UserModel::ensureNewColumns();
        DatalistPresetModel::ensureSchema();
        DatalistPresetModel::seedDefaults();

        // 2FA tab data
        $totpEnabled     = !empty($user['totp_enabled']);
        $totpSetupSecret = $_SESSION['2fa_setup_secret'] ?? null;
        $totpSetupUri    = null;
        if ($totpSetupSecret !== null) {
            $account      = $user['username'] ?? 'admin';
            $issuer       = Config::get('APP_NAME', 'StackVault');
            $totpSetupUri = TotpService::buildUri($totpSetupSecret, $account, $issuer);
        }

        // Logs tab data (always fetched — tabs switch client-side, all panes render)
        $logFilters = [
            'search'       => trim((string) $this->request->get('search',       '')),
            'entity_type'  => trim((string) $this->request->get('entity_type',  '')),
            'action_group' => trim((string) $this->request->get('action_group', '')),
            'date_from'    => trim((string) $this->request->get('date_from',    '')),
            'date_to'      => trim((string) $this->request->get('date_to',      '')),
        ];
        $logPage       = max(1, (int) $this->request->get('page', 1));
        $logTotal      = ActivityLogModel::count($logFilters);
        $logTotalPages = (int) ceil($logTotal / ActivityLogModel::PER_PAGE);
        $logPage       = min($logPage, max(1, $logTotalPages));
        $logs          = ActivityLogModel::getAll($logFilters, $logPage);
        $entityTypes   = ActivityLogModel::getDistinctEntityTypes();

        // Users tab data (admin only)
        $isAdmin  = ($user['role'] ?? '') === 'admin';
        $allUsers = $isAdmin ? UserModel::getAll() : [];

        $this->view('settings/index', [
            'title'            => 'Settings',
            'breadcrumbs'      => [['label' => 'Settings']],
            'activeTab'        => $activeTab,
            'currentUser'      => $user,
            'isAdmin'          => $isAdmin,
            'allUsers'         => $allUsers,
            'userCount'        => count($allUsers),
            'appNameValue'     => SettingsModel::get('app_name', Config::get('APP_NAME', 'StackVault')),
            'whitelistEnabled' => SettingsModel::isWhitelistEnabled(),
            'whitelistIps'     => SettingsModel::getWhitelistIps(),
            'currentIp'        => $this->request->ip(),
            'totpEnabled'      => $totpEnabled,
            'totpSetupSecret'  => $totpSetupSecret,
            'totpSetupUri'     => $totpSetupUri,
            // General tab — preset manager
            'presetGroups'     => DatalistPresetModel::getAllGroups(),
            'presetGroupLabels'=> DatalistPresetModel::GROUP_LABELS,
            // Logs tab
            'logFilters'       => $logFilters,
            'logPage'          => $logPage,
            'logTotal'         => $logTotal,
            'logTotalPages'    => $logTotalPages,
            'logs'             => $logs,
            'logPerPage'       => ActivityLogModel::PER_PAGE,
            'entityTypes'      => $entityTypes,
            'actionGroups'     => ActivityLogModel::ACTION_GROUPS,
            'entityLabels'     => ActivityLogModel::ENTITY_LABELS,
            'urlPrefixes'      => ActivityLogModel::ENTITY_URL_PREFIX,
        ]);
    }

    // ─── POST /settings/presets/add ──────────────────────────────────────────

    public function addPreset(): void
    {
        $this->validateCsrf();

        DatalistPresetModel::ensureSchema();
        DatalistPresetModel::seedDefaults();

        $group = trim((string) $this->request->post('group', ''));
        $value = trim((string) $this->request->post('value', ''));

        if (!array_key_exists($group, DatalistPresetModel::GROUP_LABELS)) {
            flash('error', 'Invalid preset group.');
            $this->redirect('/settings?tab=general');
        }

        if ($value === '') {
            flash('error', 'Preset value cannot be empty.');
            $this->redirect('/settings?tab=general');
        }

        $added = DatalistPresetModel::add($group, $value);

        if ($added) {
            flash('success', 'Preset added.');
        } else {
            flash('error', 'That value already exists in this group.');
        }

        $this->redirect('/settings?tab=general');
    }

    // ─── POST /settings/presets/{id}/delete ──────────────────────────────────

    public function deletePreset(): void
    {
        $this->validateCsrf();

        $id     = (int) $this->request->param('id', 0);
        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        DatalistPresetModel::ensureSchema();

        if ($id <= 0) {
            $this->presetResponse($isAjax, false, 'Invalid preset.');
            return;
        }

        $inUse = DatalistPresetModel::getInUseCount($id);
        if ($inUse > 0) {
            $noun = $inUse === 1 ? 'reminder is' : 'reminders are';
            $this->presetResponse($isAjax, false, "Cannot delete — {$inUse} {$noun} using this type.");
            return;
        }

        $deleted = DatalistPresetModel::delete($id);

        if ($deleted) {
            $this->presetResponse($isAjax, true, 'Preset removed.');
        } else {
            $this->presetResponse($isAjax, false, 'Cannot remove a built-in default preset.');
        }
    }

    /** Send JSON for AJAX requests or flash+redirect for standard form posts. */
    private function presetResponse(bool $isAjax, bool $success, string $message): void
    {
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => $success, 'message' => $message]);
            exit;
        }

        flash($success ? 'success' : 'error', $message);
        $this->redirect('/settings?tab=general');
    }

    // ─── POST /settings/profile ───────────────────────────────────────────────

    public function saveProfile(): void
    {
        $this->validateCsrf();

        $userId    = (int) $_SESSION['user_id'];
        $username  = trim((string) $this->request->post('username',         ''));
        $email     = trim((string) $this->request->post('email',            ''));
        $newPw     =       (string) $this->request->post('new_password',    '');
        $confirmPw =       (string) $this->request->post('confirm_password','');

        // ── Validate username ─────────────────────────────────────────────────
        if ($username === '') {
            flash('error', 'Username is required.');
            $this->redirect('/settings?tab=profile');
        }
        if (mb_strlen($username) > 32) {
            flash('error', 'Username must be 32 characters or fewer.');
            $this->redirect('/settings?tab=profile');
        }
        if (!preg_match('/^[a-zA-Z0-9_.\\-]+$/', $username)) {
            flash('error', 'Username may only contain letters, numbers, underscores, hyphens, and dots.');
            $this->redirect('/settings?tab=profile');
        }

        // ── Validate email ────────────────────────────────────────────────────
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'A valid email address is required.');
            $this->redirect('/settings?tab=profile');
        }

        // ── Uniqueness checks ─────────────────────────────────────────────────
        if (UserModel::usernameExists($username, $userId)) {
            flash('error', 'That username is already taken.');
            $this->redirect('/settings?tab=profile');
        }
        if (UserModel::emailExists($email, $userId)) {
            flash('error', 'That email address is already in use.');
            $this->redirect('/settings?tab=profile');
        }

        // ── Update profile ────────────────────────────────────────────────────
        UserModel::updateProfile($userId, $username, $email);

        // Keep session in sync
        $_SESSION['user']['username'] = $username;
        $_SESSION['user']['email']    = $email;

        // ── Change login password (optional) ──────────────────────────────────
        $passwordChanged = false;
        if ($newPw !== '') {
            if (strlen($newPw) < 8) {
                flash('error', 'New password must be at least 8 characters.');
                $this->redirect('/settings?tab=profile');
            }
            if ($newPw !== $confirmPw) {
                flash('error', 'New password and confirmation do not match.');
                $this->redirect('/settings?tab=profile');
            }
            $hash = password_hash($newPw, PASSWORD_ARGON2ID, [
                'memory_cost' => 65536,
                'time_cost'   => 4,
                'threads'     => 1,
            ]);
            UserModel::updatePassword($userId, $hash);
            $passwordChanged = true;
        }

        AuthService::log(
            $userId,
            'profile_updated',
            'user',
            $userId,
            'Profile updated' . ($passwordChanged ? ' (login password changed)' : ''),
            $this->request->ip(),
            $this->request->userAgent()
        );

        flash('success', 'Profile saved successfully.');
        $this->redirect('/settings?tab=profile');
    }

    // ─── POST /settings/vault-password ────────────────────────────────────────

    public function saveVaultPassword(): void
    {
        $this->validateCsrf();

        $userId    = (int) $_SESSION['user_id'];
        $currentPw = (string) $this->request->post('current_vault_password', '');
        $newPw     = (string) $this->request->post('new_vault_password',     '');
        $confirmPw = (string) $this->request->post('confirm_vault_password', '');

        if ($currentPw === '' || $newPw === '' || $confirmPw === '') {
            flash('error', 'All vault password fields are required.');
            $this->redirect('/settings?tab=vault');
        }
        if ($newPw !== $confirmPw) {
            flash('error', 'New vault password and confirmation do not match.');
            $this->redirect('/settings?tab=vault');
        }
        if (strlen($newPw) < 8) {
            flash('error', 'New vault password must be at least 8 characters.');
            $this->redirect('/settings?tab=vault');
        }

        $user = UserModel::findById($userId);
        if (!$user || empty($user['vault_key_encrypted'])) {
            flash('error', 'Vault is not configured. Please run setup again.');
            $this->redirect('/settings?tab=vault');
        }

        // Decrypt vault key with current password
        $rawKey = AuthService::decryptVaultKey($user['vault_key_encrypted'], $currentPw);
        if ($rawKey === false) {
            flash('error', 'Current vault password is incorrect.');
            $this->redirect('/settings?tab=vault');
        }

        // Re-encrypt with new password and update DB
        $newEncrypted = SettingsModel::encryptVaultKey($rawKey, $newPw);
        $newHash = password_hash($newPw, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536,
            'time_cost'   => 4,
            'threads'     => 1,
        ]);
        UserModel::updateVaultKey($userId, $newEncrypted, $newHash);

        // If vault was unlocked the session key is the same raw key — stays unlocked

        AuthService::log(
            $userId,
            'vault_password_changed',
            'user',
            $userId,
            'Vault password changed',
            $this->request->ip(),
            $this->request->userAgent()
        );

        flash('success', 'Vault password updated successfully.');
        $this->redirect('/settings?tab=vault');
    }

    // ─── POST /settings/whitelist/toggle ──────────────────────────────────────

    public function toggleWhitelist(): void
    {
        $this->validateCsrf();

        $enable = $this->request->post('whitelist_enabled') === '1';

        SettingsModel::migrate();

        if ($enable) {
            $ips = SettingsModel::getWhitelistIps();
            if (empty($ips)) {
                // Auto-add current IP so the admin can't lock themselves out
                $currentIp = $this->request->ip();
                SettingsModel::addToWhitelist($currentIp, 'Auto-added on enable');
                flash('info', "Whitelist enabled. Your IP ({$currentIp}) was automatically added to prevent lockout.");
            } else {
                flash('success', 'Login whitelist enabled.');
            }
        } else {
            flash('success', 'Login whitelist disabled.');
        }

        SettingsModel::setWhitelistEnabled($enable);
        $this->redirect('/settings?tab=whitelist');
    }

    // ─── POST /settings/whitelist/add ─────────────────────────────────────────

    public function addWhitelistIp(): void
    {
        $this->validateCsrf();

        $ip    = trim((string) $this->request->post('ip_address', ''));
        $label = trim((string) $this->request->post('label',      ''));

        if ($ip === '') {
            flash('error', 'IP address is required.');
            $this->redirect('/settings?tab=whitelist');
        }
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            flash('error', "'{$ip}' is not a valid IP address.");
            $this->redirect('/settings?tab=whitelist');
        }

        SettingsModel::migrate();
        $added = SettingsModel::addToWhitelist($ip, $label);

        if ($added) {
            flash('success', "IP {$ip} added to whitelist.");
        } else {
            flash('error', "IP {$ip} is already in the whitelist.");
        }

        $this->redirect('/settings?tab=whitelist');
    }

    // ─── POST /settings/2fa/setup ─────────────────────────────────────────────

    public function setup2fa(): void
    {
        $this->validateCsrf();

        TotpService::migrate();

        // Generate a fresh secret and park it in session until the user confirms
        $_SESSION['2fa_setup_secret'] = TotpService::generateSecret();

        $this->redirect('/settings?tab=2fa');
    }

    // ─── POST /settings/2fa/confirm ───────────────────────────────────────────

    public function confirm2fa(): void
    {
        $this->validateCsrf();

        $secret = $_SESSION['2fa_setup_secret'] ?? '';

        if ($secret === '') {
            flash('error', 'No setup in progress. Please click "Set Up 2FA" to begin.');
            $this->redirect('/settings?tab=2fa');
        }

        $code = trim((string) $this->request->post('totp_code', ''));

        if (strlen($code) !== 6 || !ctype_digit($code)) {
            flash('error', 'Please enter a valid 6-digit code.');
            $this->redirect('/settings?tab=2fa');
        }

        if (!TotpService::verify($secret, $code)) {
            flash('error', 'Incorrect code. Check your authenticator app and try again.');
            $this->redirect('/settings?tab=2fa');
        }

        // Verified — encrypt and persist
        $userId    = (int) $_SESSION['user_id'];
        $encrypted = TotpService::encryptSecret($secret);
        UserModel::updateTotp($userId, $encrypted, true);

        // Clear the must_setup_2fa reminder flag now that 2FA is configured
        UserModel::setMustSetup2fa($userId, false);
        $_SESSION['user']['must_setup_2fa'] = 0;

        unset($_SESSION['2fa_setup_secret']);

        AuthService::log(
            $userId, '2fa_enabled', 'user', $userId,
            'Two-factor authentication enabled',
            $this->request->ip(), $this->request->userAgent()
        );

        flash('success', 'Two-factor authentication is now active on your account.');
        $this->redirect('/settings?tab=2fa');
    }

    // ─── POST /settings/2fa/disable ───────────────────────────────────────────

    public function disable2fa(): void
    {
        $this->validateCsrf();

        $userId = (int) $_SESSION['user_id'];
        UserModel::clearTotp($userId);

        unset($_SESSION['2fa_setup_secret']);

        AuthService::log(
            $userId, '2fa_disabled', 'user', $userId,
            'Two-factor authentication disabled',
            $this->request->ip(), $this->request->userAgent()
        );

        flash('success', 'Two-factor authentication has been disabled.');
        $this->redirect('/settings?tab=2fa');
    }

    // ─── POST /settings/whitelist/{id}/delete ────────────────────────────────

    public function deleteWhitelistIp(): void
    {
        $this->validateCsrf();

        $id = (int) $this->request->param('id');
        if ($id <= 0) {
            $this->notFound();
        }

        SettingsModel::migrate();
        SettingsModel::removeFromWhitelist($id);

        flash('success', 'IP address removed from whitelist.');
        $this->redirect('/settings?tab=whitelist');
    }

    // ─── POST /settings/users/create ─────────────────────────────────────────

    public function createUser(): void
    {
        $this->validateCsrf();

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            $this->forbidden();
        }

        UserModel::ensureNewColumns();

        // ── Max 2 users ───────────────────────────────────────────────────────
        if (UserModel::count() >= 2) {
            flash('error', 'Maximum of 2 user accounts allowed.');
            $this->redirect('/settings?tab=users');
        }

        // ── Vault must be unlocked to encrypt the vault key for the new user ─
        if (!vault_unlocked()) {
            flash('error', 'Unlock the vault before creating a new user — their vault access requires it.');
            $this->redirect('/settings?tab=users');
        }

        $username     = trim((string) $this->request->post('username',       ''));
        $email        = trim((string) $this->request->post('email',          ''));
        $loginPw      =       (string) $this->request->post('login_password',  '');
        $vaultPw      =       (string) $this->request->post('vault_password',  '');
        $confirmPw    =       (string) $this->request->post('confirm_password','');

        // ── Validate username ─────────────────────────────────────────────────
        if ($username === '') {
            flash('error', 'Username is required.');
            $this->redirect('/settings?tab=users');
        }
        if (mb_strlen($username) > 32 || !preg_match('/^[a-zA-Z0-9_.\\-]+$/', $username)) {
            flash('error', 'Username must be 1–32 characters and contain only letters, numbers, underscores, hyphens, or dots.');
            $this->redirect('/settings?tab=users');
        }
        if (UserModel::usernameExists($username)) {
            flash('error', 'That username is already taken.');
            $this->redirect('/settings?tab=users');
        }

        // ── Email (optional — generate placeholder if blank) ──────────────────
        if ($email === '') {
            $email = $username . '@stackvault.local';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'The email address is not valid.');
            $this->redirect('/settings?tab=users');
        } elseif (UserModel::emailExists($email)) {
            flash('error', 'That email address is already in use.');
            $this->redirect('/settings?tab=users');
        }

        // ── Passwords ─────────────────────────────────────────────────────────
        if (strlen($loginPw) < 8) {
            flash('error', 'Login password must be at least 8 characters.');
            $this->redirect('/settings?tab=users');
        }
        if ($loginPw !== $confirmPw) {
            flash('error', 'Login password and confirmation do not match.');
            $this->redirect('/settings?tab=users');
        }
        if (strlen($vaultPw) < 8) {
            flash('error', 'Vault password must be at least 8 characters.');
            $this->redirect('/settings?tab=users');
        }

        // ── Hash + encrypt ────────────────────────────────────────────────────
        $argonOpts       = ['memory_cost' => 65536, 'time_cost' => 4, 'threads' => 1];
        $loginHash       = password_hash($loginPw, PASSWORD_ARGON2ID, $argonOpts);
        $vaultHash       = password_hash($vaultPw, PASSWORD_ARGON2ID, $argonOpts);
        $vaultKeyEncrypted = SettingsModel::encryptVaultKey($_SESSION['vault_key'], $vaultPw);

        $newId = UserModel::create(
            $username, $email, $loginHash,
            $vaultKeyEncrypted, $vaultHash,
            'admin', true
        );

        AuthService::log(
            (int) $_SESSION['user_id'], 'user_created', 'user', $newId,
            "Created user account: {$username}",
            $this->request->ip(), $this->request->userAgent()
        );

        flash('success', "User <strong>" . e($username) . "</strong> created. They will be prompted to set up 2FA on first login.");
        $this->redirect('/settings?tab=users');
    }

    // ─── POST /settings/users/{id}/delete ─────────────────────────────────────

    public function deleteUser(): void
    {
        $this->validateCsrf();

        if (($_SESSION['user']['role'] ?? '') !== 'admin') {
            $this->forbidden();
        }

        $targetId  = (int) $this->request->param('id', 0);
        $currentId = (int) $_SESSION['user_id'];

        if ($targetId <= 0) {
            $this->notFound();
        }
        if ($targetId === $currentId) {
            flash('error', 'You cannot delete your own account.');
            $this->redirect('/settings?tab=users');
        }

        $target = UserModel::findById($targetId);
        if (!$target) {
            $this->notFound();
        }

        // Revoke all remember tokens for the deleted user
        try {
            TotpRememberModel::revokeAll($targetId);
        } catch (\Throwable) {}

        UserModel::deleteById($targetId);

        AuthService::log(
            $currentId, 'user_deleted', 'user', $targetId,
            "Deleted user account: {$target['username']}",
            $this->request->ip(), $this->request->userAgent()
        );

        flash('success', 'User account deleted.');
        $this->redirect('/settings?tab=users');
    }

    // ─── POST /settings/logs/clear ────────────────────────────────────────────

    public function clearLogs(): void
    {
        $this->validateCsrf();

        ActivityLogModel::clearAll();

        AuthService::log(
            (int) $_SESSION['user_id'],
            'logs_cleared',
            'system', 0,
            'All activity logs cleared',
            $this->request->ip(),
            $this->request->userAgent()
        );

        flash('success', 'All activity logs have been cleared.');
        $this->redirect('/settings?tab=logs');
    }
}

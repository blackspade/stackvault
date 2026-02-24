<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

class VaultController extends Controller
{
    // ─── GET /vault/unlock ────────────────────────────────────────────────────

    public function showUnlock(): void
    {
        if (vault_unlocked()) {
            $this->redirect('/dashboard');
        }

        $this->view('vault/unlock', [
            'title'       => 'Unlock Vault',
            'breadcrumbs' => [['label' => 'Unlock Vault']],
            'errors'      => get_flash('error'),
        ]);
    }

    // ─── POST /vault/unlock ───────────────────────────────────────────────────

    public function unlock(): void
    {
        $this->validateCsrf();

        $vaultPassword = (string) $this->request->post('vault_password', '');
        $ip            = $this->request->ip();
        $ua            = $this->request->userAgent();

        if ($vaultPassword === '') {
            flash('error', 'Vault password is required.');
            $this->redirect('/vault/unlock');
        }

        $userId = (int) $_SESSION['user_id'];
        $result = AuthService::unlockVault($userId, $vaultPassword, $ip, $ua);

        if (!$result['success']) {
            flash('error', $result['error']);
            $this->redirect('/vault/unlock');
        }

        // Store raw 32-byte vault key in session
        $_SESSION['vault_key'] = $result['vault_key'];

        flash('success', 'Vault unlocked. Credentials are now accessible.');
        $this->redirect('/dashboard');
    }

    // ─── POST /vault/lock ─────────────────────────────────────────────────────

    public function lock(): void
    {
        $this->validateCsrf();

        if (isset($_SESSION['vault_key'])) {
            $_SESSION['vault_key'] = str_repeat("\x00", 32);
            unset($_SESSION['vault_key']);
        }

        AuthService::log(
            (int) $_SESSION['user_id'],
            'vault_locked',
            'user',
            (int) $_SESSION['user_id'],
            'Vault manually locked',
            $this->request->ip(),
            $this->request->userAgent()
        );

        flash('info', 'Vault locked. Credentials are now masked.');
        $this->redirect('/dashboard');
    }
}

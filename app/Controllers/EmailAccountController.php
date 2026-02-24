<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\EmailAccountModel;
use App\Models\ClientModel;
use App\Models\DomainModel;
use App\Services\AuthService;
use App\Services\EncryptionService;

class EmailAccountController extends Controller
{
    // ─── GET /email ───────────────────────────────────────────────────────────

    public function index(): void
    {
        $search   = trim((string) $this->request->get('search',    ''));
        $clientId = (int)          $this->request->get('client_id', 0);
        $domainId = (int)          $this->request->get('domain_id', 0);

        $accounts = EmailAccountModel::getAll($search, $clientId, $domainId);
        $clients  = ClientModel::getForSelect();
        $domains  = DomainModel::getForSelect();

        $this->view('email/index', [
            'title'        => 'Email Accounts',
            'accounts'     => $accounts,
            'clients'      => $clients,
            'domains'      => $domains,
            'search'       => $search,
            'filterClient' => $clientId,
            'filterDomain' => $domainId,
        ]);
    }

    // ─── GET /email/create ────────────────────────────────────────────────────

    public function showCreate(): void
    {
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $presetDomainId = (int) trim((string) $this->request->get('domain_id', 0));
        $returnTo       = trim((string) $this->request->get('return_to', ''));

        $this->view('email/create', [
            'title'       => 'Add Email Account',
            'breadcrumbs' => [
                ['label' => 'Email Accounts', 'url' => '/email'],
                ['label' => 'Add Account'],
            ],
            'old'            => $old,
            'errors'         => $errors,
            'clients'        => ClientModel::getForSelect(),
            'domains'        => DomainModel::getForSelect(),
            'presetDomainId' => $presetDomainId,
            'return_to'      => $returnTo,
        ]);
    }

    // ─── POST /email/store ────────────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        $returnTo = trim((string) $this->request->post('return_to', ''));

        if (!vault_unlocked() && $this->request->post('password', '') !== '') {
            $this->flashError('Vault must be unlocked to store an email password.');
            $this->redirect('/vault/unlock');
        }

        $data   = $this->collectFormData();
        $errors = $this->validateData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $qs = '?';
            if ($data['domain_id']) {
                $qs .= 'domain_id=' . $data['domain_id'] . '&';
            }
            if ($returnTo !== '') {
                $qs .= 'return_to=' . urlencode($returnTo);
            }
            $this->redirect('/email/create' . rtrim($qs, '?&'));
        }

        $data['password_encrypted'] = ($data['password'] !== '' && vault_unlocked())
            ? EncryptionService::encryptField($data['password'])
            : null;

        $data['created_by'] = $_SESSION['user_id'];
        $id = EmailAccountModel::create($data);

        $this->logActivity('email_account_created', $id,
            "Email account created: {$data['email_address']}");

        $this->flashSuccess("Email account \"{$data['email_address']}\" added.");

        if ($returnTo !== '' && str_starts_with($returnTo, '/domains/')) {
            $this->redirect($returnTo . '#tab-email');
        }

        $this->redirect("/email/{$id}");
    }

    // ─── GET /email/{id} ──────────────────────────────────────────────────────

    public function show(): void
    {
        $account = $this->resolveAccount();

        $this->view('email/show', [
            'title'       => e($account['email_address']),
            'breadcrumbs' => [
                ['label' => 'Email Accounts', 'url' => '/email'],
                ['label' => $account['email_address']],
            ],
            'account'  => $account,
            'activity' => EmailAccountModel::getActivity((int) $account['id']),
        ]);
    }

    // ─── GET /email/{id}/edit ─────────────────────────────────────────────────

    public function showEdit(): void
    {
        $account = $this->resolveAccount();
        $old     = $this->popOldInput();
        $errors  = get_flash('error');
        $merged  = array_merge($account, $old);

        $this->view('email/edit', [
            'title'       => 'Edit Email Account',
            'breadcrumbs' => [
                ['label' => 'Email Accounts', 'url' => '/email'],
                ['label' => $account['email_address'], 'url' => "/email/{$account['id']}"],
                ['label' => 'Edit'],
            ],
            'account' => $merged,
            'errors'  => $errors,
            'clients' => ClientModel::getForSelect(),
            'domains' => DomainModel::getForSelect(),
        ]);
    }

    // ─── POST /email/{id}/update ──────────────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $account = $this->resolveAccount();
        $id      = (int) $account['id'];
        $data    = $this->collectFormData();
        $errors  = $this->validateData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect("/email/{$id}/edit");
        }

        // Blank password = keep existing; new password = re-encrypt (requires vault)
        if ($data['password'] !== '') {
            if (!vault_unlocked()) {
                $this->flashError('Vault must be unlocked to change the email password.');
                $this->redirect('/vault/unlock');
            }
            $data['password_encrypted'] = EncryptionService::encryptField($data['password']);
        } else {
            $data['password_encrypted'] = $account['password_encrypted'];
        }

        EmailAccountModel::update($id, $data);

        $this->logActivity('email_account_updated', $id,
            "Email account updated: {$data['email_address']}");

        $this->flashSuccess("Email account \"{$data['email_address']}\" updated.");
        $this->redirect("/email/{$id}");
    }

    // ─── POST /email/{id}/delete ──────────────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $account = $this->resolveAccount();
        $id      = (int) $account['id'];
        $addr    = $account['email_address'];

        EmailAccountModel::delete($id);

        $this->logActivity('email_account_deleted', $id,
            "Email account deleted: {$addr}");

        $this->flashSuccess("Email account \"{$addr}\" deleted.");
        $this->redirect('/email');
    }

    // ─── POST /email/{id}/reveal ──────────────────────────────────────────────
    // AJAX — returns JSON { value: string } or { error: string }

    public function reveal(): void
    {
        $token = $this->request->post('_token', '');
        if (!$token || !hash_equals(csrf_token(), $token)) {
            $this->json(['error' => 'Invalid security token.'], 403);
        }

        if (!vault_unlocked()) {
            $this->json(['error' => 'Vault is locked. Unlock it first.'], 403);
        }

        $account   = $this->resolveAccount();
        $id        = (int) $account['id'];
        $encrypted = $account['password_encrypted'] ?? '';

        if (!$encrypted) {
            $this->json(['value' => '']);
        }

        $plain = EncryptionService::decryptField($encrypted);
        if ($plain === false) {
            $this->json(['error' => 'Decryption failed. Vault key may be invalid.'], 500);
        }

        $this->logActivity('email_password_revealed', $id,
            "Password revealed for email account: {$account['email_address']}");

        $this->json(['value' => $plain]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveAccount(): array
    {
        $id      = (int) $this->request->param('id', 0);
        $account = $id > 0 ? EmailAccountModel::getById($id) : null;

        if (!$account) {
            $this->notFound();
        }

        return $account;
    }

    private function collectFormData(): array
    {
        return [
            'email_address' => trim((string) $this->request->post('email_address', '')),
            'mail_host'     => trim((string) $this->request->post('mail_host',     '')),
            'smtp_port'     => (int)          $this->request->post('smtp_port',    EmailAccountModel::DEFAULT_SMTP),
            'imap_port'     => (int)          $this->request->post('imap_port',    EmailAccountModel::DEFAULT_IMAP),
            'username'      => trim((string) $this->request->post('username',      '')),
            'password'      => (string)       $this->request->post('password',     ''),
            'webmail_url'   => trim((string) $this->request->post('webmail_url',   '')),
            'client_id'     => (int)          $this->request->post('client_id',    0),
            'domain_id'     => (int)          $this->request->post('domain_id',    0),
            'notes'         => trim((string) $this->request->post('notes',         '')),
        ];
    }

    private function validateData(array $data): array
    {
        $errors = [];

        if ($data['email_address'] === '') {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($data['email_address'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email address is not valid.';
        } elseif (mb_strlen($data['email_address']) > 255) {
            $errors[] = 'Email address must be 255 characters or fewer.';
        }

        if ($data['smtp_port'] !== 0 && ($data['smtp_port'] < 1 || $data['smtp_port'] > 65535)) {
            $errors[] = 'SMTP port must be between 1 and 65535.';
        }

        if ($data['imap_port'] !== 0 && ($data['imap_port'] < 1 || $data['imap_port'] > 65535)) {
            $errors[] = 'IMAP port must be between 1 and 65535.';
        }

        if ($data['webmail_url'] !== '' && !filter_var($data['webmail_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Webmail URL is not a valid URL.';
        }

        return $errors;
    }

    private function popOldInput(): array
    {
        $raw = get_flash('old')[0] ?? '';
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }

    private function logActivity(string $action, int $entityId, string $description): void
    {
        AuthService::log(
            (int) $_SESSION['user_id'],
            $action,
            'email_account',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}

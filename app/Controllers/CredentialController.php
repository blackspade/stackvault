<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\CredentialModel;
use App\Models\ClientModel;
use App\Models\ServerModel;
use App\Models\DomainModel;
use App\Services\AuthService;
use App\Services\EncryptionService;

class CredentialController extends Controller
{
    // ─── GET /credentials ────────────────────────────────────────────────────

    public function index(): void
    {
        $search   = trim((string) $this->request->get('search', ''));
        $type     = trim((string) $this->request->get('type', ''));
        $clientId = (int) $this->request->get('client_id', 0);

        $credentials = CredentialModel::getAll($search, $type, $clientId);
        $clients     = ClientModel::getForSelect();

        $this->view('credentials/index', [
            'title'        => 'Credentials',
            'credentials'  => $credentials,
            'clients'      => $clients,
            'types'        => CredentialModel::TYPES,
            'search'       => $search,
            'filterType'   => $type,
            'filterClient' => $clientId,
        ]);
    }

    // ─── GET /credentials/create ─────────────────────────────────────────────

    public function showCreate(): void
    {
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $this->view('credentials/create', [
            'title'       => 'Add Credential',
            'breadcrumbs' => [
                ['label' => 'Credentials', 'url' => '/credentials'],
                ['label' => 'Add Credential'],
            ],
            'old'     => $old,
            'errors'  => $errors,
            'types'   => CredentialModel::TYPES,
            'clients' => ClientModel::getForSelect(),
            'servers' => ServerModel::getForSelect(),
            'domains' => DomainModel::getForSelect(),
        ]);
    }

    // ─── POST /credentials/store ─────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        if (!vault_unlocked()) {
            $this->flashError('Vault must be unlocked to add credentials.');
            $this->redirect('/vault/unlock');
        }

        $data   = $this->collectFormData();
        $errors = $this->validateCredentialData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect('/credentials/create');
        }

        $data['password_encrypted']    = $data['password']    !== ''
            ? EncryptionService::encryptField($data['password'])
            : null;
        $data['totp_secret_encrypted'] = $data['totp_secret'] !== ''
            ? EncryptionService::encryptField($data['totp_secret'])
            : null;

        $data['created_by'] = $_SESSION['user_id'];
        $id = CredentialModel::create($data);

        $this->logActivity('credential_created', $id,
            "Credential created: {$data['label']}");

        $this->flashSuccess("Credential \"{$data['label']}\" added.");
        $this->redirect("/credentials/{$id}");
    }

    // ─── GET /credentials/{id} ────────────────────────────────────────────────

    public function show(): void
    {
        $credential = $this->resolveCredential();

        $this->view('credentials/show', [
            'title'      => e($credential['label']),
            'breadcrumbs' => [
                ['label' => 'Credentials', 'url' => '/credentials'],
                ['label' => $credential['label']],
            ],
            'credential' => $credential,
            'types'      => CredentialModel::TYPES,
            'activity'   => CredentialModel::getActivity((int) $credential['id']),
        ]);
    }

    // ─── GET /credentials/{id}/edit ──────────────────────────────────────────

    public function showEdit(): void
    {
        $credential = $this->resolveCredential();
        $old        = $this->popOldInput();
        $errors     = get_flash('error');

        $merged = array_merge($credential, $old);

        $this->view('credentials/edit', [
            'title'       => 'Edit Credential',
            'breadcrumbs' => [
                ['label' => 'Credentials', 'url' => '/credentials'],
                ['label' => $credential['label'], 'url' => "/credentials/{$credential['id']}"],
                ['label' => 'Edit'],
            ],
            'credential' => $merged,
            'errors'     => $errors,
            'types'      => CredentialModel::TYPES,
            'clients'    => ClientModel::getForSelect(),
            'servers'    => ServerModel::getForSelect(),
            'domains'    => DomainModel::getForSelect(),
        ]);
    }

    // ─── POST /credentials/{id}/update ───────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        if (!vault_unlocked()) {
            $this->flashError('Vault must be unlocked to edit credentials.');
            $this->redirect('/vault/unlock');
        }

        $credential = $this->resolveCredential();
        $id         = (int) $credential['id'];

        $data   = $this->collectFormData();
        $errors = $this->validateCredentialData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect("/credentials/{$id}/edit");
        }

        // Blank = keep existing encrypted value
        $data['password_encrypted'] = $data['password'] !== ''
            ? EncryptionService::encryptField($data['password'])
            : $credential['password_encrypted'];

        $data['totp_secret_encrypted'] = $data['totp_secret'] !== ''
            ? EncryptionService::encryptField($data['totp_secret'])
            : $credential['totp_secret_encrypted'];

        CredentialModel::update($id, $data);

        $this->logActivity('credential_updated', $id,
            "Credential updated: {$data['label']}");

        $this->flashSuccess("Credential \"{$data['label']}\" updated.");
        $this->redirect("/credentials/{$id}");
    }

    // ─── POST /credentials/{id}/delete ───────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $credential = $this->resolveCredential();
        $id         = (int) $credential['id'];
        $label      = $credential['label'];

        CredentialModel::delete($id);

        $this->logActivity('credential_deleted', $id,
            "Credential deleted: {$label}");

        $this->flashSuccess("Credential \"{$label}\" deleted.");
        $this->redirect('/credentials');
    }

    // ─── POST /credentials/{id}/reveal ───────────────────────────────────────
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

        $credential = $this->resolveCredential();
        $id         = (int) $credential['id'];
        $field      = $this->request->post('field', 'password');

        $column    = ($field === 'totp') ? 'totp_secret_encrypted' : 'password_encrypted';
        $encrypted = $credential[$column] ?? '';

        if (!$encrypted) {
            $this->json(['value' => '']);
        }

        $plain = EncryptionService::decryptField($encrypted);

        if ($plain === false) {
            $this->json(['error' => 'Decryption failed. Vault key may be invalid.'], 500);
        }

        CredentialModel::markViewed($id, (int) $_SESSION['user_id']);

        $this->logActivity('credential_revealed', $id,
            "Password revealed for: {$credential['label']}");

        $this->json(['value' => $plain]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveCredential(): array
    {
        $id         = (int) $this->request->param('id', 0);
        $credential = $id > 0 ? CredentialModel::getById($id) : null;

        if (!$credential) {
            $this->notFound();
        }

        return $credential;
    }

    private function collectFormData(): array
    {
        return [
            'label'           => trim((string) $this->request->post('label',           '')),
            'credential_type' => trim((string) $this->request->post('credential_type', 'other')),
            'client_id'       => (int)          $this->request->post('client_id',      0),
            'server_id'       => (int)          $this->request->post('server_id',      0),
            'domain_id'       => (int)          $this->request->post('domain_id',      0),
            'username'        => trim((string) $this->request->post('username',        '')),
            'password'        => (string)       $this->request->post('password',       ''),
            'port'            => (int)          $this->request->post('port',           0),
            'totp_secret'     => trim((string) $this->request->post('totp_secret',     '')),
            'notes'           => trim((string) $this->request->post('notes',           '')),
        ];
    }

    private function validateCredentialData(array $data): array
    {
        $errors = [];

        if ($data['label'] === '') {
            $errors[] = 'Label is required.';
        } elseif (mb_strlen($data['label']) > 255) {
            $errors[] = 'Label must be 255 characters or fewer.';
        }

        if (!array_key_exists($data['credential_type'], CredentialModel::TYPES)) {
            $errors[] = 'Invalid credential type.';
        }

        if ($data['port'] !== 0 && ($data['port'] < 1 || $data['port'] > 65535)) {
            $errors[] = 'Port must be between 1 and 65535.';
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
            'credential',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}

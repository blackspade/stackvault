<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\DatabaseModel;
use App\Models\ClientModel;
use App\Models\ServerModel;
use App\Models\ApplicationModel;
use App\Services\AuthService;
use App\Services\EncryptionService;

class DatabaseController extends Controller
{
    // ─── GET /databases ───────────────────────────────────────────────────────

    public function index(): void
    {
        $search   = trim((string) $this->request->get('search', ''));
        $type     = trim((string) $this->request->get('type', ''));
        $clientId = (int) $this->request->get('client_id', 0);

        $databases = DatabaseModel::getAll($search, $type, $clientId);
        $clients   = ClientModel::getForSelect();

        $this->view('databases/index', [
            'title'        => 'Databases',
            'databases'    => $databases,
            'clients'      => $clients,
            'types'        => DatabaseModel::TYPES,
            'search'       => $search,
            'filterType'   => $type,
            'filterClient' => $clientId,
        ]);
    }

    // ─── GET /databases/create ────────────────────────────────────────────────

    public function showCreate(): void
    {
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $this->view('databases/create', [
            'title'       => 'Add Database',
            'breadcrumbs' => [
                ['label' => 'Databases', 'url' => '/databases'],
                ['label' => 'Add Database'],
            ],
            'old'      => $old,
            'errors'   => $errors,
            'types'    => DatabaseModel::TYPES,
            'clients'  => ClientModel::getForSelect(),
            'servers'  => ServerModel::getForSelect(),
            'apps'     => ApplicationModel::getForSelect(),
        ]);
    }

    // ─── POST /databases/store ────────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        if (!vault_unlocked() && $this->request->post('password', '') !== '') {
            $this->flashError('Vault must be unlocked to store a database password.');
            $this->redirect('/vault/unlock');
        }

        $data   = $this->collectFormData();
        $errors = $this->validateData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect('/databases/create');
        }

        $data['password_encrypted'] = ($data['password'] !== '' && vault_unlocked())
            ? EncryptionService::encryptField($data['password'])
            : null;

        $data['created_by'] = $_SESSION['user_id'];
        $id = DatabaseModel::create($data);

        $this->logActivity('database_created', $id,
            "Database created: {$data['db_name']} ({$data['db_type']})");

        $this->flashSuccess("Database \"{$data['db_name']}\" added.");
        $this->redirect("/databases/{$id}");
    }

    // ─── GET /databases/{id} ──────────────────────────────────────────────────

    public function show(): void
    {
        $db = $this->resolveDb();

        $this->view('databases/show', [
            'title'       => e($db['db_name']),
            'breadcrumbs' => [
                ['label' => 'Databases', 'url' => '/databases'],
                ['label' => $db['db_name']],
            ],
            'db'       => $db,
            'types'    => DatabaseModel::TYPES,
            'activity' => DatabaseModel::getActivity((int) $db['id']),
        ]);
    }

    // ─── GET /databases/{id}/edit ─────────────────────────────────────────────

    public function showEdit(): void
    {
        $db     = $this->resolveDb();
        $old    = $this->popOldInput();
        $errors = get_flash('error');
        $merged = array_merge($db, $old);

        $this->view('databases/edit', [
            'title'       => 'Edit Database',
            'breadcrumbs' => [
                ['label' => 'Databases', 'url' => '/databases'],
                ['label' => $db['db_name'], 'url' => "/databases/{$db['id']}"],
                ['label' => 'Edit'],
            ],
            'db'      => $merged,
            'errors'  => $errors,
            'types'   => DatabaseModel::TYPES,
            'clients' => ClientModel::getForSelect(),
            'servers' => ServerModel::getForSelect(),
            'apps'    => ApplicationModel::getForSelect(),
        ]);
    }

    // ─── POST /databases/{id}/update ──────────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $db     = $this->resolveDb();
        $id     = (int) $db['id'];
        $data   = $this->collectFormData();
        $errors = $this->validateData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect("/databases/{$id}/edit");
        }

        // Blank password = keep existing; new password = re-encrypt (requires vault)
        if ($data['password'] !== '') {
            if (!vault_unlocked()) {
                $this->flashError('Vault must be unlocked to change the database password.');
                $this->redirect('/vault/unlock');
            }
            $data['password_encrypted'] = EncryptionService::encryptField($data['password']);
        } else {
            $data['password_encrypted'] = $db['password_encrypted'];
        }

        DatabaseModel::update($id, $data);

        $this->logActivity('database_updated', $id,
            "Database updated: {$data['db_name']}");

        $this->flashSuccess("Database \"{$data['db_name']}\" updated.");
        $this->redirect("/databases/{$id}");
    }

    // ─── POST /databases/{id}/delete ──────────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $db   = $this->resolveDb();
        $id   = (int) $db['id'];
        $name = $db['db_name'];

        DatabaseModel::delete($id);

        $this->logActivity('database_deleted', $id,
            "Database deleted: {$name}");

        $this->flashSuccess("Database \"{$name}\" deleted.");
        $this->redirect('/databases');
    }

    // ─── POST /databases/{id}/reveal ──────────────────────────────────────────
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

        $db        = $this->resolveDb();
        $id        = (int) $db['id'];
        $encrypted = $db['password_encrypted'] ?? '';

        if (!$encrypted) {
            $this->json(['value' => '']);
        }

        $plain = EncryptionService::decryptField($encrypted);
        if ($plain === false) {
            $this->json(['error' => 'Decryption failed. Vault key may be invalid.'], 500);
        }

        $this->logActivity('database_password_revealed', $id,
            "Password revealed for database: {$db['db_name']}");

        $this->json(['value' => $plain]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveDb(): array
    {
        $id = (int) $this->request->param('id', 0);
        $db = $id > 0 ? DatabaseModel::getById($id) : null;

        if (!$db) {
            $this->notFound();
        }

        return $db;
    }

    private function collectFormData(): array
    {
        return [
            'db_name'   => trim((string) $this->request->post('db_name',   '')),
            'db_type'   => trim((string) $this->request->post('db_type',   'mysql')),
            'host'      => trim((string) $this->request->post('host',      'localhost')),
            'port'      => (int)          $this->request->post('port',     0),
            'username'  => trim((string) $this->request->post('username',  '')),
            'password'  => (string)       $this->request->post('password', ''),
            'client_id' => (int)          $this->request->post('client_id', 0),
            'server_id' => (int)          $this->request->post('server_id', 0),
            'app_id'    => (int)          $this->request->post('app_id',    0),
            'notes'     => trim((string) $this->request->post('notes',    '')),
        ];
    }

    private function validateData(array $data): array
    {
        $errors = [];

        if ($data['db_name'] === '') {
            $errors[] = 'Database name is required.';
        } elseif (mb_strlen($data['db_name']) > 255) {
            $errors[] = 'Database name must be 255 characters or fewer.';
        }

        if (!array_key_exists($data['db_type'], DatabaseModel::TYPES)) {
            $errors[] = 'Invalid database type.';
        }

        if ($data['host'] === '') {
            $errors[] = 'Host is required.';
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
            'database',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}

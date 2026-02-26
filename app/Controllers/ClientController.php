<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ClientModel;
use App\Models\ClientDocModel;
use App\Services\AuthService;

class ClientController extends Controller
{
    // ─── GET /clients ─────────────────────────────────────────────────────────

    public function index(): void
    {
        $search  = trim((string) $this->request->get('search', ''));
        $clients = ClientModel::getAll($search);

        $this->view('clients/index', [
            'title'   => 'Clients',
            'clients' => $clients,
            'search'  => $search,
        ]);
    }

    // ─── GET /clients/create ──────────────────────────────────────────────────

    public function showCreate(): void
    {
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $this->view('clients/create', [
            'title'       => 'Add Client',
            'breadcrumbs' => [
                ['label' => 'Clients', 'url' => '/clients'],
                ['label' => 'Add Client'],
            ],
            'old'    => $old,
            'errors' => $errors,
        ]);
    }

    // ─── POST /clients/store ──────────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        $data   = $this->collectFormData();
        $errors = $this->validateClientData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect('/clients/create');
        }

        $data['created_by'] = $_SESSION['user_id'];
        $id = ClientModel::create($data);

        $this->logActivity('client_created', $id,
            "Client created: {$data['name']}");

        $this->flashSuccess("Client \"{$data['name']}\" added successfully.");
        $this->redirect("/clients/{$id}");
    }

    // ─── GET /clients/{id} ────────────────────────────────────────────────────

    public function show(): void
    {
        $client = $this->resolveClient();
        ClientDocModel::ensureSchema();
        $id  = (int) $client['id'];
        $doc = ClientDocModel::getByClient($id);

        $this->view('clients/show', [
            'title'        => e($client['name']),
            'breadcrumbs'  => [
                ['label' => 'Clients', 'url' => '/clients'],
                ['label' => $client['name']],
            ],
            'client'       => $client,
            'domains'      => ClientModel::getDomains($id),
            'servers'      => ClientModel::getServers($id),
            'credentials'  => ClientModel::getCredentials($id),
            'applications' => ClientModel::getApplications($id),
            'activity'     => ClientModel::getActivity($id),
            'doc'          => $doc,
        ]);
    }

    // ─── POST /clients/{id}/docs/save ─────────────────────────────────────────

    public function saveDocs(): void
    {
        $this->validateCsrf();

        $client = $this->resolveClient();
        $id     = (int) $client['id'];

        $content      = (string) $this->request->post('doc_content', '');
        $ipTablesRaw  = (string) $this->request->post('ip_tables', '[]');

        // Validate ip_tables is valid JSON
        $decoded = json_decode($ipTablesRaw, true);
        if (!is_array($decoded)) {
            $ipTablesRaw = '[]';
        }

        ClientDocModel::ensureSchema();
        ClientDocModel::save($id, $content, $ipTablesRaw);

        $this->logActivity('client_docs_saved', $id,
            "Documentation saved for: {$client['name']}");

        $this->redirect("/clients/{$id}?tab=docs");
    }

    // ─── GET /clients/{id}/edit ───────────────────────────────────────────────

    public function showEdit(): void
    {
        $client = $this->resolveClient();
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        // Old input overrides saved values when bouncing back from a validation error
        $merged = array_merge($client, $old);

        $this->view('clients/edit', [
            'title'       => 'Edit Client',
            'breadcrumbs' => [
                ['label' => 'Clients', 'url' => '/clients'],
                ['label' => $client['name'], 'url' => "/clients/{$client['id']}"],
                ['label' => 'Edit'],
            ],
            'client'  => $merged,
            'errors'  => $errors,
        ]);
    }

    // ─── POST /clients/{id}/update ────────────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $client = $this->resolveClient();
        $id     = (int) $client['id'];

        $data   = $this->collectFormData();
        $errors = $this->validateClientData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect("/clients/{$id}/edit");
        }

        ClientModel::update($id, $data);

        $this->logActivity('client_updated', $id,
            "Client updated: {$data['name']}");

        $this->flashSuccess("Client \"{$data['name']}\" updated.");
        $this->redirect("/clients/{$id}");
    }

    // ─── POST /clients/{id}/delete ────────────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $client = $this->resolveClient();
        $id     = (int) $client['id'];
        $name   = $client['name'];

        ClientModel::delete($id);

        $this->logActivity('client_deleted', $id,
            "Client deleted: {$name}");

        $this->flashSuccess("Client \"{$name}\" deleted.");
        $this->redirect('/clients');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** Load client by route param, 404 if not found. */
    private function resolveClient(): array
    {
        $id     = (int) $this->request->param('id', 0);
        $client = $id > 0 ? ClientModel::getById($id) : null;

        if (!$client) {
            $this->notFound();
        }

        return $client;
    }

    /** Collect and sanitise form fields. */
    private function collectFormData(): array
    {
        return [
            'name'          => trim((string) $this->request->post('name',          '')),
            'contact_name'  => trim((string) $this->request->post('contact_name',  '')),
            'contact_email' => trim((string) $this->request->post('contact_email', '')),
            'contact_phone' => trim((string) $this->request->post('contact_phone', '')),
            'website'       => trim((string) $this->request->post('website',       '')),
            'notes'         => trim((string) $this->request->post('notes',         '')),
            'is_active'     => (bool) $this->request->post('is_active', false),
        ];
    }

    /** Return array of error strings (empty = valid). */
    private function validateClientData(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Client name is required.';
        } elseif (mb_strlen($data['name']) > 255) {
            $errors[] = 'Client name must be 255 characters or fewer.';
        }

        if ($data['contact_email'] !== ''
            && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)
        ) {
            $errors[] = 'Contact email is not a valid email address.';
        }

        if ($data['website'] !== ''
            && !filter_var($data['website'], FILTER_VALIDATE_URL)
        ) {
            $errors[] = 'Website must be a valid URL (include https://).';
        }

        return $errors;
    }

    /** Pop old-input JSON from flash, return as array. */
    private function popOldInput(): array
    {
        $raw = get_flash('old')[0] ?? '';
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }

    /** Write to the activity log (silently absorbs failures). */
    private function logActivity(string $action, int $entityId, string $description): void
    {
        AuthService::log(
            (int) $_SESSION['user_id'],
            $action,
            'client',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}

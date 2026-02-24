<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ClientModel;
use App\Models\DatalistPresetModel;
use App\Models\ServerModel;
use App\Services\AuthService;

class ServerController extends Controller
{
    // ─── GET /servers ─────────────────────────────────────────────────────────

    public function index(): void
    {
        $search  = trim((string) $this->request->get('search', ''));
        $servers = ServerModel::getAll($search);

        $this->view('servers/index', [
            'title'   => 'Servers',
            'servers' => $servers,
            'search'  => $search,
        ]);
    }

    // ─── GET /servers/create ──────────────────────────────────────────────────

    public function showCreate(): void
    {
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        DatalistPresetModel::init();

        $this->view('servers/create', [
            'title'       => 'Add Server',
            'breadcrumbs' => [
                ['label' => 'Servers', 'url' => '/servers'],
                ['label' => 'Add Server'],
            ],
            'old'             => $old,
            'errors'          => $errors,
            'clients'         => ClientModel::getForSelect(),
            'osPresets'       => DatalistPresetModel::getValues('server_os'),
            'providerPresets' => DatalistPresetModel::getValues('server_provider'),
        ]);
    }

    // ─── POST /servers/store ──────────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        $data   = $this->collectFormData();
        $errors = $this->validateServerData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect('/servers/create');
        }

        $data['created_by'] = $_SESSION['user_id'];
        $id = ServerModel::create($data);

        $this->logActivity('server_created', $id,
            "Server created: {$data['label']}");

        $this->flashSuccess("Server \"{$data['label']}\" added.");
        $this->redirect("/servers/{$id}");
    }

    // ─── GET /servers/{id} ────────────────────────────────────────────────────

    public function show(): void
    {
        $server = $this->resolveServer();
        $id     = (int) $server['id'];

        $this->view('servers/show', [
            'title'        => e($server['label']),
            'breadcrumbs'  => [
                ['label' => 'Servers', 'url' => '/servers'],
                ['label' => $server['label']],
            ],
            'server'       => $server,
            'applications' => ServerModel::getApplications($id),
            'databases'    => ServerModel::getDatabases($id),
            'domains'      => ServerModel::getDomains($id),
            'credentials'  => ServerModel::getCredentials($id),
            'activity'     => ServerModel::getActivity($id),
        ]);
    }

    // ─── GET /servers/{id}/edit ───────────────────────────────────────────────

    public function showEdit(): void
    {
        $server = $this->resolveServer();
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $merged = array_merge($server, $old);

        DatalistPresetModel::init();

        $this->view('servers/edit', [
            'title'       => 'Edit Server',
            'breadcrumbs' => [
                ['label' => 'Servers', 'url' => '/servers'],
                ['label' => $server['label'], 'url' => "/servers/{$server['id']}"],
                ['label' => 'Edit'],
            ],
            'server'          => $merged,
            'errors'          => $errors,
            'clients'         => ClientModel::getForSelect(),
            'osPresets'       => DatalistPresetModel::getValues('server_os'),
            'providerPresets' => DatalistPresetModel::getValues('server_provider'),
        ]);
    }

    // ─── POST /servers/{id}/update ────────────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $server = $this->resolveServer();
        $id     = (int) $server['id'];

        $data   = $this->collectFormData();
        $errors = $this->validateServerData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect("/servers/{$id}/edit");
        }

        ServerModel::update($id, $data);

        $this->logActivity('server_updated', $id,
            "Server updated: {$data['label']}");

        $this->flashSuccess("Server \"{$data['label']}\" updated.");
        $this->redirect("/servers/{$id}");
    }

    // ─── POST /servers/{id}/delete ────────────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $server = $this->resolveServer();
        $id     = (int) $server['id'];
        $label  = $server['label'];

        ServerModel::delete($id);

        $this->logActivity('server_deleted', $id,
            "Server deleted: {$label}");

        $this->flashSuccess("Server \"{$label}\" deleted.");
        $this->redirect('/servers');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveServer(): array
    {
        $id     = (int) $this->request->param('id', 0);
        $server = $id > 0 ? ServerModel::getById($id) : null;

        if (!$server) {
            $this->notFound();
        }

        return $server;
    }

    private function collectFormData(): array
    {
        return [
            'label'             => trim((string) $this->request->post('label',             '')),
            'client_id'         => (int)          $this->request->post('client_id',         0),
            'ip_address'        => trim((string) $this->request->post('ip_address',        '')),
            'hostname'          => trim((string) $this->request->post('hostname',          '')),
            'provider'          => trim((string) $this->request->post('provider',          '')),
            'os_version'        => trim((string) $this->request->post('os_version',        '')),
            'ssh_port'          => (int)          $this->request->post('ssh_port',          22),
            'monitoring_status' => trim((string) $this->request->post('monitoring_status', 'unknown')),
            'firewall_notes'    => trim((string) $this->request->post('firewall_notes',    '')),
            'installed_stacks'  => trim((string) $this->request->post('installed_stacks',  '')),
            'notes'             => trim((string) $this->request->post('notes',             '')),
        ];
    }

    private function validateServerData(array $data): array
    {
        $errors = [];

        if ($data['label'] === '') {
            $errors[] = 'Server label is required.';
        } elseif (mb_strlen($data['label']) > 255) {
            $errors[] = 'Server label must be 255 characters or fewer.';
        }

        if ($data['ip_address'] !== ''
            && !filter_var($data['ip_address'], FILTER_VALIDATE_IP)
        ) {
            $errors[] = 'IP address is not valid (IPv4 or IPv6).';
        }

        if ($data['ssh_port'] < 1 || $data['ssh_port'] > 65535) {
            $errors[] = 'SSH port must be between 1 and 65535.';
        }

        $validStatuses = ['unknown', 'online', 'offline', 'degraded'];
        if (!in_array($data['monitoring_status'], $validStatuses, true)) {
            $errors[] = 'Invalid monitoring status.';
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
            'server',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}

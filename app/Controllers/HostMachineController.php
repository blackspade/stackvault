<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\HostMachineModel;
use App\Models\ClientModel;
use App\Services\AuthService;

class HostMachineController extends Controller
{
    // ─── GET /hosts ───────────────────────────────────────────────────────────

    public function index(): void
    {
        $search   = trim((string) $this->request->get('search',    ''));
        $clientId = (int)          $this->request->get('client_id', 0);
        $os       = trim((string) $this->request->get('os',        ''));

        $machines = HostMachineModel::getAll($search, $clientId, $os);
        $clients  = ClientModel::getForSelect();

        $this->view('hosts/index', [
            'title'        => 'Host Files',
            'machines'     => $machines,
            'clients'      => $clients,
            'search'       => $search,
            'filterClient' => $clientId,
            'filterOs'     => $os,
        ]);
    }

    // ─── GET /hosts/create ────────────────────────────────────────────────────

    public function showCreate(): void
    {
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $this->view('hosts/create', [
            'title'       => 'New Host File',
            'breadcrumbs' => [
                ['label' => 'Host Files', 'url' => '/hosts'],
                ['label' => 'New'],
            ],
            'old'     => $old,
            'errors'  => $errors,
            'clients' => ClientModel::getForSelect(),
        ]);
    }

    // ─── POST /hosts/store ────────────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        $data   = $this->collectFormData();
        $errors = $this->validateData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect('/hosts/create');
        }

        $data['created_by'] = $_SESSION['user_id'];
        $id = HostMachineModel::create($data);

        $this->logActivity('host_machine_created', $id, "Host file created: {$data['name']}");

        $this->flashSuccess("Host file \"{$data['name']}\" created.");
        $this->redirect("/hosts/{$id}");
    }

    // ─── GET /hosts/{id} ──────────────────────────────────────────────────────

    public function show(): void
    {
        $machine = $this->resolveMachine();

        $this->view('hosts/show', [
            'title'       => $machine['name'],
            'breadcrumbs' => [
                ['label' => 'Host Files', 'url' => '/hosts'],
                ['label' => $machine['name']],
            ],
            'machine'  => $machine,
            'activity' => HostMachineModel::getActivity((int) $machine['id']),
        ]);
    }

    // ─── GET /hosts/{id}/edit ─────────────────────────────────────────────────

    public function showEdit(): void
    {
        $machine = $this->resolveMachine();
        $old     = $this->popOldInput();
        $errors  = get_flash('error');
        $merged  = array_merge($machine, $old);

        $this->view('hosts/edit', [
            'title'       => 'Edit Host File',
            'breadcrumbs' => [
                ['label' => 'Host Files', 'url' => '/hosts'],
                ['label' => $machine['name'], 'url' => "/hosts/{$machine['id']}"],
                ['label' => 'Edit'],
            ],
            'machine' => $merged,
            'errors'  => $errors,
            'clients' => ClientModel::getForSelect(),
        ]);
    }

    // ─── POST /hosts/{id}/update ──────────────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $machine = $this->resolveMachine();
        $id      = (int) $machine['id'];
        $data    = $this->collectFormData();
        $errors  = $this->validateData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect("/hosts/{$id}/edit");
        }

        HostMachineModel::update($id, $data);

        $this->logActivity('host_machine_updated', $id, "Host file updated: {$data['name']}");

        $this->flashSuccess("Host file \"{$data['name']}\" saved.");
        $this->redirect("/hosts/{$id}");
    }

    // ─── POST /hosts/{id}/delete ──────────────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $machine = $this->resolveMachine();
        $id      = (int) $machine['id'];
        $name    = $machine['name'];

        HostMachineModel::delete($id);

        $this->logActivity('host_machine_deleted', $id, "Host file deleted: {$name}");

        $this->flashSuccess("Host file \"{$name}\" deleted.");
        $this->redirect('/hosts');
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function resolveMachine(): array
    {
        $id      = (int) $this->request->param('id', 0);
        $machine = $id > 0 ? HostMachineModel::getById($id) : null;

        if (!$machine) {
            $this->notFound();
        }

        return $machine;
    }

    private function collectFormData(): array
    {
        return [
            'name'        => trim((string) $this->request->post('name',        '')),
            'os'          => trim((string) $this->request->post('os',          'other')),
            'client_id'   => (int)          $this->request->post('client_id',   0),
            'hosts_file'  => (string)       $this->request->post('hosts_file',  ''),
            'description' => trim((string) $this->request->post('description', '')),
        ];
    }

    private function validateData(array $data): array
    {
        $errors = [];

        if ($data['name'] === '') {
            $errors[] = 'Machine name is required.';
        } elseif (mb_strlen($data['name']) > 255) {
            $errors[] = 'Machine name must be 255 characters or fewer.';
        }

        if ($data['os'] !== '' && !array_key_exists($data['os'], HostMachineModel::OS_TYPES)) {
            $errors[] = 'Invalid OS selection.';
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
            'host_machine',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\ClientModel;
use App\Models\DomainModel;
use App\Services\AuthService;

class DomainController extends Controller
{
    // ─── GET /domains ─────────────────────────────────────────────────────────

    public function index(): void
    {
        $search  = trim((string) $this->request->get('search', ''));
        $domains = DomainModel::getAll($search);

        $this->view('domains/index', [
            'title'   => 'Domains',
            'domains' => $domains,
            'search'  => $search,
        ]);
    }

    // ─── GET /domains/create ──────────────────────────────────────────────────

    public function showCreate(): void
    {
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $this->view('domains/create', [
            'title'       => 'Add Domain',
            'breadcrumbs' => [
                ['label' => 'Domains', 'url' => '/domains'],
                ['label' => 'Add Domain'],
            ],
            'old'     => $old,
            'errors'  => $errors,
            'clients' => ClientModel::getForSelect(),
        ]);
    }

    // ─── POST /domains/store ──────────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        $data   = $this->collectFormData();
        $errors = $this->validateDomainData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect('/domains/create');
        }

        $data['created_by'] = $_SESSION['user_id'];
        $id = DomainModel::create($data);

        $this->logActivity('domain_created', $id,
            "Domain created: {$data['root_domain']}");

        $this->flashSuccess("Domain \"{$data['root_domain']}\" added.");
        $this->redirect("/domains/{$id}");
    }

    // ─── GET /domains/{id} ────────────────────────────────────────────────────

    public function show(): void
    {
        $domain = $this->resolveDomain();
        $id     = (int) $domain['id'];

        $this->view('domains/show', [
            'title'        => e($domain['root_domain']),
            'breadcrumbs'  => [
                ['label' => 'Domains', 'url' => '/domains'],
                ['label' => $domain['root_domain']],
            ],
            'domain'       => $domain,
            'dnsRecords'   => DomainModel::getDnsRecords($id),
            'emailAccounts'=> DomainModel::getEmailAccounts($id),
            'applications' => DomainModel::getApplications($id),
            'servers'      => DomainModel::getServers($id),
            'databases'    => DomainModel::getDatabases($id),
            'credentials'  => DomainModel::getCredentials($id),
            'activity'     => DomainModel::getActivity($id),
        ]);
    }

    // ─── GET /domains/{id}/edit ───────────────────────────────────────────────

    public function showEdit(): void
    {
        $domain = $this->resolveDomain();
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $merged = array_merge($domain, $old);

        $this->view('domains/edit', [
            'title'       => 'Edit Domain',
            'breadcrumbs' => [
                ['label' => 'Domains', 'url' => '/domains'],
                ['label' => $domain['root_domain'], 'url' => "/domains/{$domain['id']}"],
                ['label' => 'Edit'],
            ],
            'domain'  => $merged,
            'errors'  => $errors,
            'clients' => ClientModel::getForSelect(),
        ]);
    }

    // ─── POST /domains/{id}/update ────────────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $domain = $this->resolveDomain();
        $id     = (int) $domain['id'];

        $data   = $this->collectFormData();
        $errors = $this->validateDomainData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect("/domains/{$id}/edit");
        }

        DomainModel::update($id, $data);

        $this->logActivity('domain_updated', $id,
            "Domain updated: {$data['root_domain']}");

        $this->flashSuccess("Domain \"{$data['root_domain']}\" updated.");
        $this->redirect("/domains/{$id}");
    }

    // ─── POST /domains/{id}/delete ────────────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $domain = $this->resolveDomain();
        $id     = (int) $domain['id'];
        $name   = $domain['root_domain'];

        DomainModel::delete($id);

        $this->logActivity('domain_deleted', $id,
            "Domain deleted: {$name}");

        $this->flashSuccess("Domain \"{$name}\" deleted.");
        $this->redirect('/domains');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveDomain(): array
    {
        $id     = (int) $this->request->param('id', 0);
        $domain = $id > 0 ? DomainModel::getById($id) : null;

        if (!$domain) {
            $this->notFound();
        }

        return $domain;
    }

    private function collectFormData(): array
    {
        return [
            'root_domain' => trim((string) $this->request->post('root_domain', '')),
            'client_id'   => (int)    $this->request->post('client_id',   0),
            'registrar'   => trim((string) $this->request->post('registrar',   '')),
            'expiry_date' => trim((string) $this->request->post('expiry_date',  '')),
            'ssl_expiry'  => trim((string) $this->request->post('ssl_expiry',   '')),
            'nameservers' => trim((string) $this->request->post('nameservers',  '')),
            'notes'       => trim((string) $this->request->post('notes',        '')),
            'is_active'   => (bool) $this->request->post('is_active', false),
        ];
    }

    private function validateDomainData(array $data): array
    {
        $errors = [];

        if ($data['root_domain'] === '') {
            $errors[] = 'Domain name is required.';
        } elseif (mb_strlen($data['root_domain']) > 255) {
            $errors[] = 'Domain name must be 255 characters or fewer.';
        }

        if ($data['expiry_date'] !== ''
            && !strtotime($data['expiry_date'])
        ) {
            $errors[] = 'Expiry date is not a valid date.';
        }

        if ($data['ssl_expiry'] !== ''
            && !strtotime($data['ssl_expiry'])
        ) {
            $errors[] = 'SSL expiry date is not a valid date.';
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
            'domain',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}

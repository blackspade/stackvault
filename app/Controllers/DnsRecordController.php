<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\DnsRecordModel;
use App\Models\DomainModel;
use App\Services\AuthService;

class DnsRecordController extends Controller
{
    // ─── GET /dns ─────────────────────────────────────────────────────────────

    public function index(): void
    {
        $search   = trim((string) $this->request->get('search',    ''));
        $type     = trim((string) $this->request->get('type',      ''));
        $domainId = (int)          $this->request->get('domain_id', 0);

        $records = DnsRecordModel::getAll($search, $type, $domainId);
        $domains = DomainModel::getForSelect();

        $this->view('dns/index', [
            'title'        => 'DNS Records',
            'records'      => $records,
            'domains'      => $domains,
            'types'        => DnsRecordModel::TYPES,
            'search'       => $search,
            'filterType'   => $type,
            'filterDomain' => $domainId,
        ]);
    }

    // ─── GET /dns/create ──────────────────────────────────────────────────────

    public function showCreate(): void
    {
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        // Pre-select domain from query string (e.g. linked from domains/show DNS tab)
        $presetDomainId = (int) trim((string) $this->request->get('domain_id', 0));
        $returnTo       = trim((string) $this->request->get('return_to', ''));

        $this->view('dns/create', [
            'title'       => 'Add DNS Record',
            'breadcrumbs' => [
                ['label' => 'DNS Records', 'url' => '/dns'],
                ['label' => 'Add Record'],
            ],
            'old'            => $old,
            'errors'         => $errors,
            'types'          => DnsRecordModel::TYPES,
            'domains'        => DomainModel::getForSelect(),
            'presetDomainId' => $presetDomainId,
            'return_to'      => $returnTo,
        ]);
    }

    // ─── POST /dns/store ──────────────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        $returnTo = trim((string) $this->request->post('return_to', ''));
        $data     = $this->collectFormData();
        $errors   = $this->validateData($data);

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
            $this->redirect('/dns/create' . rtrim($qs, '?&'));
        }

        $id = DnsRecordModel::create($data);

        $this->logActivity('dns_record_created', $id,
            "DNS record created: {$data['record_type']} {$data['name']}");

        $this->flashSuccess("{$data['record_type']} record \"{$data['name']}\" added.");

        // Return to domain show page if we came from there
        if ($returnTo !== '' && str_starts_with($returnTo, '/domains/')) {
            $this->redirect($returnTo . '#tab-dns');
        }

        $this->redirect("/dns/{$id}");
    }

    // ─── GET /dns/{id} ────────────────────────────────────────────────────────

    public function show(): void
    {
        $record = $this->resolveRecord();

        $this->view('dns/show', [
            'title'       => e($record['record_type'] . ' ' . $record['name']),
            'breadcrumbs' => [
                ['label' => 'DNS Records', 'url' => '/dns'],
                ['label' => $record['root_domain'], 'url' => '/domains/' . $record['domain_id']],
                ['label' => $record['record_type'] . ' ' . $record['name']],
            ],
            'record' => $record,
            'types'  => DnsRecordModel::TYPES,
        ]);
    }

    // ─── GET /dns/{id}/edit ───────────────────────────────────────────────────

    public function showEdit(): void
    {
        $record = $this->resolveRecord();
        $old    = $this->popOldInput();
        $errors = get_flash('error');
        $merged = array_merge($record, $old);

        $this->view('dns/edit', [
            'title'       => 'Edit DNS Record',
            'breadcrumbs' => [
                ['label' => 'DNS Records', 'url' => '/dns'],
                ['label' => $record['root_domain'], 'url' => '/domains/' . $record['domain_id']],
                ['label' => $record['record_type'] . ' ' . $record['name'], 'url' => '/dns/' . $record['id']],
                ['label' => 'Edit'],
            ],
            'record'  => $merged,
            'errors'  => $errors,
            'types'   => DnsRecordModel::TYPES,
            'domains' => DomainModel::getForSelect(),
        ]);
    }

    // ─── POST /dns/{id}/update ────────────────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $record = $this->resolveRecord();
        $id     = (int) $record['id'];
        $data   = $this->collectFormData();
        $errors = $this->validateData($data);

        if (!empty($errors)) {
            foreach ($errors as $err) {
                flash('error', $err);
            }
            flash('old', json_encode($data));
            $this->redirect("/dns/{$id}/edit");
        }

        DnsRecordModel::update($id, $data);

        $this->logActivity('dns_record_updated', $id,
            "DNS record updated: {$data['record_type']} {$data['name']}");

        $this->flashSuccess("{$data['record_type']} record \"{$data['name']}\" updated.");
        $this->redirect("/dns/{$id}");
    }

    // ─── POST /dns/{id}/delete ────────────────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $record   = $this->resolveRecord();
        $id       = (int) $record['id'];
        $domainId = (int) $record['domain_id'];
        $label    = $record['record_type'] . ' ' . $record['name'];

        DnsRecordModel::delete($id);

        $this->logActivity('dns_record_deleted', $id,
            "DNS record deleted: {$label}");

        $this->flashSuccess("DNS record \"{$label}\" deleted.");

        // Return to domain show if referrer was there; else go to DNS index
        $returnTo = trim((string) $this->request->post('return_to', ''));
        if ($returnTo && str_starts_with($returnTo, '/domains/')) {
            $this->redirect($returnTo . '#tab-dns');
        }

        $this->redirect('/dns?domain_id=' . $domainId);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveRecord(): array
    {
        $id     = (int) $this->request->param('id', 0);
        $record = $id > 0 ? DnsRecordModel::getById($id) : null;

        if (!$record) {
            $this->notFound();
        }

        return $record;
    }

    private function collectFormData(): array
    {
        $type     = strtoupper(trim((string) $this->request->post('record_type', 'A')));
        $priority = trim((string) $this->request->post('priority', ''));

        return [
            'domain_id'   => (int)    $this->request->post('domain_id',   0),
            'record_type' => $type,
            'name'        => trim((string) $this->request->post('name',    '')),
            'value'       => trim((string) $this->request->post('value',   '')),
            'ttl'         => (int)    $this->request->post('ttl',          3600),
            'priority'    => $priority !== '' ? (int) $priority : null,
            'notes'       => trim((string) $this->request->post('notes',   '')),
        ];
    }

    private function validateData(array $data): array
    {
        $errors = [];

        if ($data['domain_id'] <= 0) {
            $errors[] = 'Domain is required.';
        }

        if (!array_key_exists($data['record_type'], DnsRecordModel::TYPES)) {
            $errors[] = 'Invalid record type.';
        }

        if ($data['name'] === '') {
            $errors[] = 'Name is required (use @ for the zone root).';
        } elseif (mb_strlen($data['name']) > 255) {
            $errors[] = 'Name must be 255 characters or fewer.';
        }

        if ($data['value'] === '') {
            $errors[] = 'Value is required.';
        }

        if ($data['ttl'] < 0 || $data['ttl'] > 2147483647) {
            $errors[] = 'TTL must be a non-negative integer.';
        }

        if (in_array($data['record_type'], DnsRecordModel::PRIORITY_TYPES, true)
            && $data['priority'] === null
        ) {
            $errors[] = 'Priority is required for ' . $data['record_type'] . ' records.';
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
            'dns_record',
            $entityId,
            $description,
            $this->request->ip(),
            $this->request->userAgent()
        );
    }
}

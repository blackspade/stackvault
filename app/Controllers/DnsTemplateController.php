<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\DnsTemplateModel;
use App\Services\AuthService;

class DnsTemplateController extends Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        DnsTemplateModel::ensureSchema();
        DnsTemplateModel::seedBuiltins();
    }

    // ─── GET /dns/templates ───────────────────────────────────────────────────

    public function index(): void
    {
        $this->view('dns_templates/index', [
            'title'     => 'DNS Templates',
            'templates' => DnsTemplateModel::getAll(),
        ]);
    }

    // ─── GET /dns/templates/create ────────────────────────────────────────────

    public function showCreate(): void
    {
        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $this->view('dns_templates/create', [
            'title'       => 'Create DNS Template',
            'breadcrumbs' => [
                ['label' => 'DNS Records',   'url' => '/dns'],
                ['label' => 'DNS Templates', 'url' => '/dns/templates'],
                ['label' => 'Create'],
            ],
            'old'    => $old,
            'errors' => $errors,
        ]);
    }

    // ─── POST /dns/templates/store ────────────────────────────────────────────

    public function store(): void
    {
        $this->validateCsrf();

        [$name, $description, $records, $errors] = $this->collectAndValidate();

        if (!empty($errors)) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            flash('old', json_encode(['name' => $name, 'description' => $description, 'records' => $records]));
            $this->redirect('/dns/templates/create');
        }

        $id = DnsTemplateModel::create($name, $description, $records);

        AuthService::log(
            (int) $_SESSION['user_id'], 'dns_template_created', 'dns_template', $id,
            "DNS template created: {$name}",
            $this->request->ip(), $this->request->userAgent()
        );

        $this->flashSuccess("Template \"{$name}\" created.");
        $this->redirect("/dns/templates/{$id}");
    }

    // ─── GET /dns/templates/{id} ──────────────────────────────────────────────

    public function show(): void
    {
        $template = $this->resolveTemplate();
        $records  = DnsTemplateModel::getRecords((int) $template['id']);

        $this->view('dns_templates/show', [
            'title'       => e($template['name']),
            'breadcrumbs' => [
                ['label' => 'DNS Records',    'url' => '/dns'],
                ['label' => 'DNS Templates',  'url' => '/dns/templates'],
                ['label' => $template['name']],
            ],
            'template' => $template,
            'records'  => $records,
        ]);
    }

    // ─── GET /dns/templates/{id}/edit ─────────────────────────────────────────

    public function showEdit(): void
    {
        $template = $this->resolveTemplate();

        if ($template['is_builtin']) {
            $this->flashError('Built-in templates cannot be edited.');
            $this->redirect('/dns/templates/' . $template['id']);
        }

        $old    = $this->popOldInput();
        $errors = get_flash('error');

        $records = DnsTemplateModel::getRecords((int) $template['id']);

        if (!empty($old)) {
            $template = array_merge($template, $old);
            $records  = $old['records'] ?? $records;
        }

        $this->view('dns_templates/edit', [
            'title'       => 'Edit Template',
            'breadcrumbs' => [
                ['label' => 'DNS Records',     'url' => '/dns'],
                ['label' => 'DNS Templates',   'url' => '/dns/templates'],
                ['label' => $template['name'], 'url' => '/dns/templates/' . $template['id']],
                ['label' => 'Edit'],
            ],
            'template' => $template,
            'records'  => $records,
            'errors'   => $errors,
        ]);
    }

    // ─── POST /dns/templates/{id}/update ──────────────────────────────────────

    public function update(): void
    {
        $this->validateCsrf();

        $template = $this->resolveTemplate();
        $id       = (int) $template['id'];

        if ($template['is_builtin']) {
            $this->flashError('Built-in templates cannot be edited.');
            $this->redirect("/dns/templates/{$id}");
        }

        [$name, $description, $records, $errors] = $this->collectAndValidate();

        if (!empty($errors)) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            flash('old', json_encode(['name' => $name, 'description' => $description, 'records' => $records]));
            $this->redirect("/dns/templates/{$id}/edit");
        }

        DnsTemplateModel::update($id, $name, $description, $records);

        AuthService::log(
            (int) $_SESSION['user_id'], 'dns_template_updated', 'dns_template', $id,
            "DNS template updated: {$name}",
            $this->request->ip(), $this->request->userAgent()
        );

        $this->flashSuccess("Template \"{$name}\" updated.");
        $this->redirect("/dns/templates/{$id}");
    }

    // ─── POST /dns/templates/{id}/delete ──────────────────────────────────────

    public function delete(): void
    {
        $this->validateCsrf();

        $template = $this->resolveTemplate();
        $id       = (int) $template['id'];
        $name     = $template['name'];

        if ($template['is_builtin']) {
            $this->flashError('Built-in templates cannot be deleted.');
            $this->redirect("/dns/templates/{$id}");
        }

        DnsTemplateModel::delete($id);

        AuthService::log(
            (int) $_SESSION['user_id'], 'dns_template_deleted', 'dns_template', $id,
            "DNS template deleted: {$name}",
            $this->request->ip(), $this->request->userAgent()
        );

        $this->flashSuccess("Template \"{$name}\" deleted.");
        $this->redirect('/dns/templates');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveTemplate(): array
    {
        $id       = (int) $this->request->param('id', 0);
        $template = $id > 0 ? DnsTemplateModel::getById($id) : null;

        if (!$template) {
            $this->notFound();
        }

        return $template;
    }

    private function collectAndValidate(): array
    {
        $name        = trim((string) $this->request->post('name', ''));
        $description = trim((string) $this->request->post('description', ''));
        $rawRecords  = $this->request->post('records', []);
        $errors      = [];

        if ($name === '') {
            $errors[] = 'Template name is required.';
        } elseif (mb_strlen($name) > 120) {
            $errors[] = 'Template name must be 120 characters or fewer.';
        }

        $records = [];
        if (is_array($rawRecords)) {
            foreach ($rawRecords as $rec) {
                $type  = strtoupper(trim((string) ($rec['record_type'] ?? '')));
                $rname = trim((string) ($rec['name']  ?? ''));
                $value = trim((string) ($rec['value'] ?? ''));

                if ($type === '' || $rname === '' || $value === '') {
                    continue; // skip blank/incomplete rows
                }

                $records[] = [
                    'record_type' => $type,
                    'name'        => $rname,
                    'value'       => $value,
                    'ttl'         => (int) ($rec['ttl'] ?: 3600),
                    'priority'    => isset($rec['priority']) && $rec['priority'] !== '' ? (int) $rec['priority'] : null,
                    'notes'       => trim((string) ($rec['notes'] ?? '')),
                ];
            }
        }

        if (empty($records)) {
            $errors[] = 'At least one DNS record row is required.';
        }

        return [$name, $description ?: null, $records, $errors];
    }

    private function popOldInput(): array
    {
        $raw = get_flash('old')[0] ?? '';
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }
}

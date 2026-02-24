<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\DnsTemplateModel;
use App\Models\DomainModel;
use App\Models\DnsRecordModel;
use App\Services\AuthService;

class DnsApplyTemplateController extends Controller
{
    public function __construct(Request $request)
    {
        parent::__construct($request);
        DnsTemplateModel::ensureSchema();
        DnsTemplateModel::seedBuiltins();
    }

    // ─── GET /dns/apply-template ──────────────────────────────────────────────

    public function showForm(): void
    {
        $templates    = DnsTemplateModel::getAllWithRecords();
        $domains      = DomainModel::getForSelect();
        $presetDomain = (int) $this->request->get('domain_id',   0);
        $presetTpl    = (int) $this->request->get('template_id', 0);

        $this->view('dns_templates/apply', [
            'title'       => 'Add Records from Template',
            'breadcrumbs' => [
                ['label' => 'DNS Records',   'url' => '/dns'],
                ['label' => 'DNS Templates', 'url' => '/dns/templates'],
                ['label' => 'Apply Template'],
            ],
            'templates'    => $templates,
            'domains'      => $domains,
            'presetDomain' => $presetDomain,
            'presetTpl'    => $presetTpl,
        ]);
    }

    // ─── POST /dns/apply-template/preview ────────────────────────────────────

    public function preview(): void
    {
        $this->validateCsrf();

        $domainId   = (int) $this->request->post('domain_id',    0);
        $templateId = (int) $this->request->post('template_id',  0);
        $ip         = trim((string) $this->request->post('var_ip',          ''));
        $mailServer = trim((string) $this->request->post('var_mail_server', ''));
        $spf        = trim((string) $this->request->post('var_spf',         ''));

        $errors = [];
        if ($domainId <= 0) {
            $errors[] = 'Please select a domain.';
        }
        if ($templateId <= 0) {
            $errors[] = 'Please select a template.';
        }

        if (!empty($errors)) {
            foreach ($errors as $e) {
                flash('error', $e);
            }
            $this->redirect('/dns/apply-template');
        }

        $template = DnsTemplateModel::getById($templateId);
        $domain   = DomainModel::getById($domainId);

        if (!$template || !$domain) {
            $this->notFound();
        }

        $tplRecords = DnsTemplateModel::getRecords($templateId);

        $vars = [
            'domain'      => $domain['root_domain'],
            'ip'          => $ip,
            'mail_server' => $mailServer,
            'spf'         => $spf,
        ];

        // Substitute variables in each record
        $substituted = array_map(
            static fn($rec) => DnsTemplateModel::substituteVars($rec, $vars),
            $tplRecords
        );

        // Build existing records index for conflict detection
        $existing      = DnsRecordModel::getByDomain($domainId);
        $existingIndex = [];
        foreach ($existing as $ex) {
            $key = strtoupper($ex['record_type']) . '|' . strtolower($ex['name']);
            $existingIndex[$key] = true;
        }

        // Tag each record with conflict status
        foreach ($substituted as &$rec) {
            $key = strtoupper($rec['record_type']) . '|' . strtolower($rec['name']);
            $rec['conflict'] = isset($existingIndex[$key]);
        }
        unset($rec);

        // Store in session for the confirm step
        $_SESSION['dns_template_preview'] = [
            'domain_id'   => $domainId,
            'template_id' => $templateId,
            'records'     => $substituted,
        ];

        $this->view('dns_templates/preview', [
            'title'       => 'Preview — ' . e($template['name']),
            'breadcrumbs' => [
                ['label' => 'DNS Records',    'url' => '/dns'],
                ['label' => 'DNS Templates',  'url' => '/dns/templates'],
                ['label' => 'Apply Template', 'url' => '/dns/apply-template'],
                ['label' => 'Preview'],
            ],
            'template' => $template,
            'domain'   => $domain,
            'records'  => $substituted,
        ]);
    }

    // ─── POST /dns/apply-template/confirm ────────────────────────────────────

    public function confirm(): void
    {
        $this->validateCsrf();

        $preview = $_SESSION['dns_template_preview'] ?? null;

        if (!$preview) {
            $this->flashError('Preview session expired. Please start again.');
            $this->redirect('/dns/apply-template');
        }

        unset($_SESSION['dns_template_preview']);

        $domainId   = (int) $preview['domain_id'];
        $templateId = (int) $preview['template_id'];
        $allRecords = $preview['records'];

        $template = DnsTemplateModel::getById($templateId);
        $domain   = DomainModel::getById($domainId);

        if (!$template || !$domain) {
            $this->flashError('Invalid template or domain.');
            $this->redirect('/dns/apply-template');
        }

        // Determine which indices the user selected
        $selectedRaw = $this->request->post('records', []);
        $selected    = is_array($selectedRaw)
            ? array_flip(array_map('intval', $selectedRaw))
            : [];

        $created = 0;
        foreach ($allRecords as $i => $rec) {
            if (!isset($selected[$i])) {
                continue;
            }

            DnsRecordModel::create([
                'domain_id'   => $domainId,
                'record_type' => $rec['record_type'],
                'name'        => $rec['name'],
                'value'       => $rec['value'],
                'ttl'         => (int) ($rec['ttl'] ?? 3600),
                'priority'    => isset($rec['priority']) && $rec['priority'] !== '' ? (int) $rec['priority'] : null,
                'notes'       => $rec['notes'] ?: null,
            ]);

            $created++;
        }

        AuthService::log(
            (int) $_SESSION['user_id'],
            'dns_template_applied',
            'dns_template',
            $templateId,
            "Applied template \"{$template['name']}\" to {$domain['root_domain']}: {$created} record(s) created.",
            $this->request->ip(),
            $this->request->userAgent()
        );

        $this->flashSuccess(
            "{$created} DNS record(s) created for {$domain['root_domain']} from template \"{$template['name']}\"."
        );

        $this->redirect('/dns?domain_id=' . $domainId);
    }
}

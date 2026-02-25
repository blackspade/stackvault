<?php
/**
 * Client detail view — tabbed
 * Vars: $client[], $domains[], $servers[], $credentials[], $applications[], $activity[], $doc
 */

// Server-side active tab — avoids bootstrap.Tab() JS dependency
$activeTab = $_GET['tab'] ?? 'overview';
$tab = fn(string $t): string => $activeTab === $t ? 'active show' : '';
$navTab = fn(string $t): string => $activeTab === $t ? 'active' : '';
?>
<link rel="stylesheet" href="<?= asset('css/quill.snow.css') ?>">
<?php

$credTypeLabel = function(string $type): string {
    return match($type) {
        'ssh'       => 'SSH',    'cpanel'    => 'cPanel',
        'database'  => 'DB',     'email'     => 'Email',
        'api_key'   => 'API Key','registrar' => 'Registrar',
        'cloud'     => 'Cloud',  default     => 'Other',
    };
};
$credTypeColor = function(string $type): string {
    return match($type) {
        'ssh'      => 'cyan',  'cpanel'   => 'orange',
        'database' => 'indigo','email'    => 'blue',
        'api_key'  => 'purple','registrar'=> 'teal',
        'cloud'    => 'azure', default    => 'secondary',
    };
};
$monStatus = function(string $s): string {
    return match($s) {
        'online'  => '<span class="badge bg-success-lt text-success">Online</span>',
        'offline' => '<span class="badge bg-danger-lt text-danger">Offline</span>',
        'degraded'=> '<span class="badge bg-warning-lt text-warning">Degraded</span>',
        default   => '<span class="badge bg-secondary-lt text-muted">Unknown</span>',
    };
};
$actionIcon = function(string $a): string {
    return match(true) {
        str_contains($a,'create') => 'ti-circle-plus',
        str_contains($a,'update') => 'ti-edit',
        str_contains($a,'delete') => 'ti-trash',
        default                   => 'ti-activity',
    };
};
?>

<!-- Page actions — shared into layout via View::share() after view renders -->
<?php ob_start(); ?>
<a href="<?= url('/clients/' . $client['id'] . '/export') ?>" target="_blank"
   class="btn btn-sm btn-outline-secondary">
    <i class="ti ti-printer me-1"></i>Export Profile
</a>
<a href="<?= url('/clients/' . $client['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary ms-1">
    <i class="ti ti-edit me-1"></i>Edit
</a>
<button type="button" class="btn btn-sm btn-outline-danger ms-1"
        onclick="confirmDelete(<?= (int)$client['id'] ?>, '<?= e(addslashes($client['name'])) ?>')">
    <i class="ti ti-trash me-1"></i>Delete
</button>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Client info bar ────────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row align-items-center g-3">

            <div class="col-auto">
                <span class="avatar avatar-lg bg-blue-lt text-blue">
                    <?= strtoupper(substr($client['name'], 0, 2)) ?>
                </span>
            </div>

            <div class="col">
                <div class="d-flex align-items-center gap-2">
                    <h2 class="mb-0"><?= e($client['name']) ?></h2>
                    <?php if ($client['is_active']): ?>
                    <span class="badge bg-success-lt text-success">Active</span>
                    <?php else: ?>
                    <span class="badge bg-secondary-lt text-muted">Inactive</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex flex-wrap gap-3 mt-1 text-muted small">
                    <?php if ($client['contact_name']): ?>
                    <span><i class="ti ti-user me-1"></i><?= e($client['contact_name']) ?></span>
                    <?php endif; ?>
                    <?php if ($client['contact_email']): ?>
                    <a href="mailto:<?= e($client['contact_email']) ?>" class="text-muted">
                        <i class="ti ti-mail me-1"></i><?= e($client['contact_email']) ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($client['contact_phone']): ?>
                    <span><i class="ti ti-phone me-1"></i><?= e($client['contact_phone']) ?></span>
                    <?php endif; ?>
                    <?php if ($client['website']): ?>
                    <a href="<?= e($client['website']) ?>" target="_blank" rel="noopener" class="text-muted">
                        <i class="ti ti-external-link me-1"></i><?= e($client['website']) ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick counts -->
            <div class="col-auto d-none d-md-flex gap-4 text-center">
                <div>
                    <div class="h3 mb-0"><?= count($domains) ?></div>
                    <div class="text-muted small">Domains</div>
                </div>
                <div>
                    <div class="h3 mb-0"><?= count($servers) ?></div>
                    <div class="text-muted small">Servers</div>
                </div>
                <div>
                    <div class="h3 mb-0"><?= count($credentials) ?></div>
                    <div class="text-muted small">Creds</div>
                </div>
                <div>
                    <div class="h3 mb-0"><?= count($applications) ?></div>
                    <div class="text-muted small">Apps</div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ── Tabs ───────────────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
            <li class="nav-item">
                <a href="#tab-overview" class="nav-link <?= $navTab('overview') ?>" data-bs-toggle="tab">
                    <i class="ti ti-info-circle me-1"></i>Overview
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-domains" class="nav-link <?= $navTab('domains') ?>" data-bs-toggle="tab">
                    <i class="ti ti-world me-1"></i>Domains
                    <?php if (count($domains)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($domains) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-servers" class="nav-link <?= $navTab('servers') ?>" data-bs-toggle="tab">
                    <i class="ti ti-server me-1"></i>Servers
                    <?php if (count($servers)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($servers) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-credentials" class="nav-link <?= $navTab('credentials') ?>" data-bs-toggle="tab">
                    <i class="ti ti-key me-1"></i>Credentials
                    <?php if (count($credentials)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($credentials) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-applications" class="nav-link <?= $navTab('applications') ?>" data-bs-toggle="tab">
                    <i class="ti ti-apps me-1"></i>Applications
                    <?php if (count($applications)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($applications) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-activity" class="nav-link <?= $navTab('activity') ?>" data-bs-toggle="tab">
                    <i class="ti ti-activity me-1"></i>Activity
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-docs" class="nav-link <?= $navTab('docs') ?>" data-bs-toggle="tab">
                    <i class="ti ti-file-text me-1"></i>Documentation
                    <?php if (!empty($doc['content']) || !empty($doc['ip_tables']) && $doc['ip_tables'] !== '[]'): ?>
                    <span class="badge bg-green text-white ms-1">
                        <i class="ti ti-check" style="font-size:0.7rem;"></i>
                    </span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body tab-content">

        <!-- ── Overview ─────────────────────────────────────────────────────── -->
        <div class="tab-pane <?= $tab('overview') ?>" id="tab-overview">
            <div class="row g-4">

                <!-- Contact details -->
                <div class="col-md-6">
                    <h4 class="mb-3">Contact Information</h4>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted" style="width:140px;">Contact Name</td>
                            <td><?= $client['contact_name'] ? e($client['contact_name']) : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email</td>
                            <td>
                                <?php if ($client['contact_email']): ?>
                                <a href="mailto:<?= e($client['contact_email']) ?>"><?= e($client['contact_email']) ?></a>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Phone</td>
                            <td><?= $client['contact_phone'] ? e($client['contact_phone']) : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Website</td>
                            <td>
                                <?php if ($client['website']): ?>
                                <a href="<?= e($client['website']) ?>" target="_blank" rel="noopener"><?= e($client['website']) ?></a>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                <?php if ($client['is_active']): ?>
                                <span class="badge bg-success-lt text-success">Active</span>
                                <?php else: ?>
                                <span class="badge bg-secondary-lt text-muted">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Added</td>
                            <td class="text-muted small"><?= e($client['created_at']) ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Documentation link -->
                <div class="col-md-6">
                    <h4 class="mb-3">Documentation</h4>
                    <a href="<?= url('/clients/' . $client['id'] . '?tab=docs') ?>" class="text-primary">
                        <i class="ti ti-file-text me-1"></i>View Documentation
                    </a>
                </div>

            </div>
        </div>

        <!-- ── Domains ───────────────────────────────────────────────────────── -->
        <div class="tab-pane <?= $tab('domains') ?>" id="tab-domains">
            <?php if (empty($domains)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-world fs-1 d-block mb-2"></i>
                No domains linked to this client.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Registrar</th>
                            <th>Expires</th>
                            <th>SSL Expires</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($domains as $d): ?>
                        <tr>
                            <td class="fw-medium"><?= e($d['root_domain']) ?></td>
                            <td class="text-muted"><?= $d['registrar'] ? e($d['registrar']) : '—' ?></td>
                            <td class="text-muted small"><?= $d['expiry_date'] ?? '—' ?></td>
                            <td class="text-muted small"><?= $d['ssl_expiry']  ?? '—' ?></td>
                            <td>
                                <?= $d['is_active']
                                    ? '<span class="badge bg-success-lt text-success">Active</span>'
                                    : '<span class="badge bg-secondary-lt text-muted">Inactive</span>' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Servers ───────────────────────────────────────────────────────── -->
        <div class="tab-pane <?= $tab('servers') ?>" id="tab-servers">
            <?php if (empty($servers)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-server fs-1 d-block mb-2"></i>
                No servers linked to this client.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>IP Address</th>
                            <th>Provider</th>
                            <th>OS</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servers as $s): ?>
                        <tr>
                            <td class="fw-medium"><?= e($s['label']) ?></td>
                            <td><code><?= e($s['ip_address'] ?? '—') ?></code></td>
                            <td class="text-muted"><?= $s['provider'] ? e($s['provider']) : '—' ?></td>
                            <td class="text-muted small"><?= $s['os_version'] ? e($s['os_version']) : '—' ?></td>
                            <td><?= $monStatus($s['monitoring_status']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Credentials ───────────────────────────────────────────────────── -->
        <div class="tab-pane <?= $tab('credentials') ?>" id="tab-credentials">
            <?php if (!vault_unlocked()): ?>
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
                <i class="ti ti-lock fs-3"></i>
                <div>
                    Vault is locked — usernames shown, passwords masked.
                    <a href="<?= url('/vault/unlock') ?>">Unlock vault to reveal credentials.</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($credentials)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-key fs-1 d-block mb-2"></i>
                No credentials linked to this client.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Username</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($credentials as $cr): ?>
                        <tr>
                            <td class="fw-medium"><?= e($cr['label']) ?></td>
                            <td>
                                <span class="badge bg-<?= $credTypeColor($cr['credential_type']) ?>-lt
                                                      text-<?= $credTypeColor($cr['credential_type']) ?>">
                                    <?= $credTypeLabel($cr['credential_type']) ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= $cr['username'] ? e($cr['username']) : '—' ?></td>
                            <td class="text-muted small" title="<?= e($cr['created_at']) ?>">
                                <?= time_ago($cr['created_at']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Applications ──────────────────────────────────────────────────── -->
        <div class="tab-pane <?= $tab('applications') ?>" id="tab-applications">
            <?php if (empty($applications)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-apps fs-1 d-block mb-2"></i>
                No applications linked to this client.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr>
                            <th>App Name</th>
                            <th>Version</th>
                            <th>Stack</th>
                            <th>Server</th>
                            <th>Deploy</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td class="fw-medium"><?= e($app['app_name']) ?></td>
                            <td class="text-muted small"><?= $app['version'] ? e($app['version']) : '—' ?></td>
                            <td class="text-muted small"><?= $app['stack_type'] ? e($app['stack_type']) : '—' ?></td>
                            <td class="text-muted small"><?= $app['server_label'] ? e($app['server_label']) : '—' ?></td>
                            <td class="text-muted small"><?= $app['deployment_method'] ? e($app['deployment_method']) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Activity ──────────────────────────────────────────────────────── -->
        <div class="tab-pane <?= $tab('activity') ?>" id="tab-activity">
            <?php if (empty($activity)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-activity fs-1 d-block mb-2"></i>
                No activity recorded for this client yet.
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($activity as $log): ?>
                <div class="list-group-item px-0 py-2">
                    <div class="d-flex align-items-start gap-3">
                        <span class="avatar avatar-xs bg-secondary-lt text-secondary mt-1">
                            <i class="ti <?= $actionIcon($log['action']) ?>"></i>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-medium small">
                                <?= $log['description'] ? e($log['description']) : e(str_replace('_', ' ', $log['action'])) ?>
                            </div>
                            <div class="text-muted" style="font-size:0.75rem;">
                                <?php if ($log['username']): ?>
                                <i class="ti ti-user me-1"></i><?= e($log['username']) ?> &middot;
                                <?php endif; ?>
                                <?php if ($log['ip_address']): ?>
                                <i class="ti ti-map-pin me-1"></i><?= e($log['ip_address']) ?> &middot;
                                <?php endif; ?>
                                <?= time_ago($log['created_at']) ?>
                            </div>
                        </div>
                        <span class="text-muted ms-auto" style="font-size:0.75rem; white-space:nowrap;"
                              title="<?= e($log['created_at']) ?>">
                            <?= date('d M Y H:i', strtotime($log['created_at'])) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Documentation ─────────────────────────────────────────────────── -->
        <div class="tab-pane <?= $tab('docs') ?>" id="tab-docs">

            <form method="POST" action="<?= url('/clients/' . $client['id'] . '/docs/save') ?>" id="docs-form">
                <?= csrf_field() ?>
                <input type="hidden" name="doc_content" id="doc_content">
                <input type="hidden" name="ip_tables" id="ip_tables_input">

                <!-- Save button row -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Documentation</h4>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>Save Documentation
                    </button>
                </div>

                <!-- Quill editor (full width) -->
                <div id="sv-doc-editor" style="min-height:320px;"></div>

                <!-- IP Tables (full width, below editor) -->
                <div class="mt-4 pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0"><i class="ti ti-network me-1"></i>IP Tables</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="svAddTable()">
                            <i class="ti ti-plus me-1"></i>Add IP Table
                        </button>
                    </div>
                    <div id="sv-ip-tables"></div>
                </div>

            </form>
        </div>

    </div><!-- /tab-content -->
</div><!-- /card -->

<!-- Hidden delete form -->
<form id="delete-form" method="post" action="" style="display:none;">
    <?= csrf_field() ?>
</form>

<script>
function confirmDelete(id, name) {
    if (!confirm('Delete client "' + name + '"?\n\nThis cannot be undone.')) return;
    const form = document.getElementById('delete-form');
    form.action = '<?= url('/clients/') ?>' + id + '/delete';
    form.submit();
}
</script>

<!-- ── Documentation tab JS (Quill + IP Table manager) ──────────────────── -->
<script src="<?= asset('js/quill.min.js') ?>"></script>
<script>
(function () {
    // ── Quill init ────────────────────────────────────────────────────────
    const quill = new Quill('#sv-doc-editor', {
        theme: 'snow',
        placeholder: 'Document this client…',
        modules: {
            toolbar: [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['blockquote', 'code-block', 'link'],
                ['clean']
            ]
        }
    });

    // Pre-fill with existing content
    const existingContent = <?= json_encode($doc['content'] ?? '') ?>;
    if (existingContent) {
        quill.root.innerHTML = existingContent;
    }

    // ── IP Tables init ────────────────────────────────────────────────────
    const existingTables = <?= json_encode(json_decode($doc['ip_tables'] ?? '[]', true) ?? []) ?>;
    const container = document.getElementById('sv-ip-tables');

    function esc(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function svMakeRow(ip, label, port, mac, notes) {
        const row = document.createElement('div');
        row.className = 'sv-ip-row row g-1 mb-1 align-items-center';
        row.innerHTML =
            '<div class="col-2"><input class="form-control form-control-sm sv-ip" value="' + esc(ip) + '" placeholder="IP Address"></div>' +
            '<div class="col-3"><input class="form-control form-control-sm sv-label" value="' + esc(label) + '" placeholder="Label / Device"></div>' +
            '<div class="col-1"><input class="form-control form-control-sm sv-port" value="' + esc(port) + '" placeholder="Port"></div>' +
            '<div class="col-2"><input class="form-control form-control-sm sv-mac" value="' + esc(mac) + '" placeholder="MAC Address"></div>' +
            '<div class="col-3"><input class="form-control form-control-sm sv-notes" value="' + esc(notes) + '" placeholder="Notes"></div>' +
            '<div class="col-1 text-end"><button type="button" class="btn btn-sm btn-ghost-danger px-1" onclick="svDeleteRow(this)" title="Remove row"><i class="ti ti-x"></i></button></div>';
        return row;
    }

    function svMakeTable(name, rows) {
        const wrap = document.createElement('div');
        wrap.className = 'sv-table-block card mb-3';
        wrap.innerHTML =
            '<div class="card-body p-3">' +
            '<div class="d-flex gap-2 mb-3 align-items-center">' +
            '<input class="form-control form-control-sm sv-table-name fw-semibold" value="' + esc(name) + '" placeholder="Table name (e.g. LAN – 192.168.30.x)">' +
            '<button type="button" class="btn btn-sm btn-ghost-danger flex-shrink-0" onclick="svDeleteTable(this)" title="Remove table"><i class="ti ti-trash"></i></button>' +
            '</div>' +
            '<div class="row g-1 mb-1 text-muted" style="font-size:0.75rem;">' +
            '<div class="col-2">IP Address</div><div class="col-3">Label / Device</div><div class="col-1">Port</div><div class="col-2">MAC Address</div><div class="col-3">Notes</div><div class="col-1"></div>' +
            '</div>' +
            '<div class="sv-rows"></div>' +
            '<button type="button" class="btn btn-sm btn-ghost-secondary mt-2" onclick="svAddRow(this)"><i class="ti ti-plus me-1"></i>Add Row</button>' +
            '</div>';
        const rowsEl = wrap.querySelector('.sv-rows');
        (rows || []).forEach(function (r) {
            rowsEl.appendChild(svMakeRow(r.ip, r.label, r.port, r.mac, r.notes));
        });
        return wrap;
    }

    // Render existing tables on load
    existingTables.forEach(function (t) {
        container.appendChild(svMakeTable(t.name, t.rows));
    });

    // Public functions used by onclick attributes
    window.svAddTable = function () {
        container.appendChild(svMakeTable('', []));
    };
    window.svAddRow = function (btn) {
        const rowsEl = btn.closest('.sv-table-block').querySelector('.sv-rows');
        rowsEl.appendChild(svMakeRow('', '', '', '', ''));
    };
    window.svDeleteRow = function (btn) {
        btn.closest('.sv-ip-row').remove();
    };
    window.svDeleteTable = function (btn) {
        btn.closest('.sv-table-block').remove();
    };

    // ── Form submit: sync hidden inputs ──────────────────────────────────
    document.getElementById('docs-form').addEventListener('submit', function () {
        document.getElementById('doc_content').value = quill.root.innerHTML;

        const tables = [];
        document.querySelectorAll('.sv-table-block').forEach(function (block) {
            const rows = [];
            block.querySelectorAll('.sv-ip-row').forEach(function (row) {
                rows.push({
                    ip:    row.querySelector('.sv-ip').value.trim(),
                    label: row.querySelector('.sv-label').value.trim(),
                    port:  row.querySelector('.sv-port').value.trim(),
                    mac:   row.querySelector('.sv-mac').value.trim(),
                    notes: row.querySelector('.sv-notes').value.trim()
                });
            });
            tables.push({ name: block.querySelector('.sv-table-name').value.trim(), rows: rows });
        });
        document.getElementById('ip_tables_input').value = JSON.stringify(tables);
    });
})();
</script>

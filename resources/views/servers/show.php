<?php
/**
 * Server detail view — tabbed
 * Vars: $server[], $applications[], $databases[], $domains[], $credentials[], $activity[]
 */
$monBadge = fn(string $s) => match($s) {
    'online'   => '<span class="badge bg-success">Online</span>',
    'offline'  => '<span class="badge bg-danger">Offline</span>',
    'degraded' => '<span class="badge bg-warning">Degraded</span>',
    default    => '<span class="badge bg-secondary-lt text-muted">Unknown</span>',
};
$credTypeLabel = fn(string $t) => match($t) {
    'ssh'=>'SSH','cpanel'=>'cPanel','database'=>'DB','email'=>'Email',
    'api_key'=>'API Key','registrar'=>'Registrar','cloud'=>'Cloud',default=>'Other'
};
$credTypeColor = fn(string $t) => match($t) {
    'ssh'=>'cyan','cpanel'=>'orange','database'=>'indigo','email'=>'blue',
    'api_key'=>'purple','registrar'=>'teal','cloud'=>'azure',default=>'secondary'
};

// SSH connect string
$sshTarget = $server['ip_address'] ?: $server['hostname'] ?: null;
$sshPort   = (int)($server['ssh_port'] ?? 22);
$sshCmd    = $sshTarget
    ? 'ssh <user>@' . $server['ip_address'] . ($sshPort !== 22 ? ' -p ' . $sshPort : '')
    : null;
?>

<?php
// ── Page actions ─────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/servers/' . $server['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="ti ti-edit me-1"></i>Edit
</a>
<button type="button" class="btn btn-sm btn-outline-danger ms-1"
        onclick="confirmDelete(<?= (int)$server['id'] ?>, '<?= e(addslashes($server['label'])) ?>')">
    <i class="ti ti-trash me-1"></i>Delete
</button>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Server header bar ─────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row align-items-center g-3">

            <div class="col-auto">
                <span class="avatar avatar-lg bg-indigo-lt text-indigo">
                    <i class="ti ti-server fs-2"></i>
                </span>
            </div>

            <div class="col">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="mb-0"><?= e($server['label']) ?></h2>
                    <?= $monBadge($server['monitoring_status']) ?>
                </div>
                <div class="d-flex flex-wrap gap-3 mt-1 text-muted small">
                    <?php if ($server['ip_address']): ?>
                    <span><i class="ti ti-network me-1"></i><code><?= e($server['ip_address']) ?></code></span>
                    <?php endif; ?>
                    <?php if ($server['hostname']): ?>
                    <span><i class="ti ti-world me-1"></i><?= e($server['hostname']) ?></span>
                    <?php endif; ?>
                    <?php if ($server['provider']): ?>
                    <span><i class="ti ti-building me-1"></i><?= e($server['provider']) ?></span>
                    <?php endif; ?>
                    <?php if ($server['os_version']): ?>
                    <span><i class="ti ti-brand-ubuntu me-1"></i><?= e($server['os_version']) ?></span>
                    <?php endif; ?>
                    <?php if ($server['client_name']): ?>
                    <a href="<?= url('/clients/' . $server['client_id']) ?>" class="text-muted">
                        <i class="ti ti-user me-1"></i><?= e($server['client_name']) ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick counts -->
            <div class="col-auto d-none d-md-flex gap-4 text-center">
                <div>
                    <div class="h3 mb-0"><?= count($applications) ?></div>
                    <div class="text-muted small">Apps</div>
                </div>
                <div>
                    <div class="h3 mb-0"><?= count($databases) ?></div>
                    <div class="text-muted small">DBs</div>
                </div>
                <div>
                    <div class="h3 mb-0"><?= count($domains) ?></div>
                    <div class="text-muted small">Domains</div>
                </div>
                <div>
                    <div class="h3 mb-0"><?= count($credentials) ?></div>
                    <div class="text-muted small">Creds</div>
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
                <a href="#tab-overview" class="nav-link active" data-bs-toggle="tab">
                    <i class="ti ti-info-circle me-1"></i>Overview
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-apps" class="nav-link" data-bs-toggle="tab">
                    <i class="ti ti-apps me-1"></i>Applications
                    <?php if (count($applications)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($applications) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-databases" class="nav-link" data-bs-toggle="tab">
                    <i class="ti ti-database me-1"></i>Databases
                    <?php if (count($databases)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($databases) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-domains" class="nav-link" data-bs-toggle="tab">
                    <i class="ti ti-world me-1"></i>Domains
                    <?php if (count($domains)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($domains) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-credentials" class="nav-link" data-bs-toggle="tab">
                    <i class="ti ti-key me-1"></i>Credentials
                    <?php if (count($credentials)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($credentials) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-activity" class="nav-link" data-bs-toggle="tab">
                    <i class="ti ti-activity me-1"></i>Activity
                </a>
            </li>
        </ul>
    </div>

    <div class="card-body tab-content">

        <!-- ── Overview ─────────────────────────────────────────────────────── -->
        <div class="tab-pane active show" id="tab-overview">
            <div class="row g-4">

                <!-- Server details -->
                <div class="col-md-6">
                    <h4 class="mb-3">Server Information</h4>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted" style="width:160px;">Label</td>
                            <td class="fw-medium"><?= e($server['label']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">IP Address</td>
                            <td><?= $server['ip_address'] ? '<code>' . e($server['ip_address']) . '</code>' : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Hostname</td>
                            <td><?= $server['hostname'] ? e($server['hostname']) : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">SSH Port</td>
                            <td><code><?= $sshPort ?></code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Provider</td>
                            <td><?= $server['provider'] ? e($server['provider']) : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">OS</td>
                            <td><?= $server['os_version'] ? e($server['os_version']) : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Client</td>
                            <td>
                                <?php if ($server['client_name']): ?>
                                <a href="<?= url('/clients/' . $server['client_id']) ?>"><?= e($server['client_name']) ?></a>
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td><?= $monBadge($server['monitoring_status']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Added</td>
                            <td class="text-muted small"><?= e($server['created_at']) ?></td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">

                    <!-- SSH Quick Connect -->
                    <?php if ($sshCmd): ?>
                    <h4 class="mb-3">SSH Quick Connect</h4>
                    <div class="input-group mb-4">
                        <span class="input-group-text bg-dark text-white border-dark">
                            <i class="ti ti-terminal-2"></i>
                        </span>
                        <input type="text" id="ssh-cmd" class="form-control font-monospace bg-dark text-white border-dark"
                               value="<?= e($sshCmd) ?>" readonly>
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="copyToClipboard('ssh-cmd', this)" title="Copy">
                            <i class="ti ti-copy"></i>
                        </button>
                    </div>
                    <?php if ($sshPort !== 22): ?>
                    <div class="text-muted small mb-3">Non-standard SSH port: <code><?= $sshPort ?></code></div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- Installed Stacks -->
                    <?php if ($server['installed_stacks']): ?>
                    <h4 class="mb-2">Installed Stacks</h4>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <?php
                        $stacks = preg_split('/[\r\n,]+/', $server['installed_stacks']);
                        foreach (array_filter(array_map('trim', $stacks)) as $stack):
                        ?>
                        <span class="badge bg-indigo-lt text-indigo"><?= e($stack) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Firewall Notes -->
                    <?php if ($server['firewall_notes']): ?>
                    <h4 class="mb-2">Firewall Notes</h4>
                    <div class="bg-light rounded p-3 mb-4" style="white-space:pre-wrap;font-size:.875rem;"><?= e($server['firewall_notes']) ?></div>
                    <?php endif; ?>

                    <!-- Notes -->
                    <?php if ($server['notes']): ?>
                    <h4 class="mb-2">Notes</h4>
                    <div class="bg-light rounded p-3" style="white-space:pre-wrap;font-size:.875rem;"><?= e($server['notes']) ?></div>
                    <?php endif; ?>

                    <?php if (!$sshCmd && !$server['installed_stacks'] && !$server['firewall_notes'] && !$server['notes']): ?>
                    <p class="text-muted mt-2">No additional information recorded.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- ── Applications ──────────────────────────────────────────────────── -->
        <div class="tab-pane" id="tab-apps">
            <?php if (empty($applications)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-apps fs-1 d-block mb-2"></i>
                No applications on this server.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr><th>App</th><th>Version</th><th>Stack</th><th>Domain</th><th>Path</th><th>Deploy</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td class="fw-medium"><?= e($app['app_name']) ?></td>
                            <td class="text-muted small"><?= $app['version'] ? e($app['version']) : '—' ?></td>
                            <td class="text-muted small"><?= $app['stack_type'] ? e($app['stack_type']) : '—' ?></td>
                            <td class="text-muted small"><?= $app['root_domain'] ? e($app['root_domain']) : '—' ?></td>
                            <td>
                                <?php if ($app['install_path']): ?>
                                <code class="small"><?= e($app['install_path']) ?></code>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= $app['deployment_method'] ? e($app['deployment_method']) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Databases ─────────────────────────────────────────────────────── -->
        <div class="tab-pane" id="tab-databases">
            <?php if (empty($databases)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-database fs-1 d-block mb-2"></i>
                No databases on this server.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr><th>DB Name</th><th>Type</th><th>Host</th><th>Port</th><th>User</th><th>App</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($databases as $db): ?>
                        <tr>
                            <td class="fw-medium"><?= e($db['db_name']) ?></td>
                            <td><span class="badge bg-indigo-lt text-indigo"><?= e(strtoupper($db['db_type'])) ?></span></td>
                            <td class="text-muted small"><code><?= e($db['host']) ?></code></td>
                            <td class="text-muted small"><?= (int)$db['port'] ?></td>
                            <td class="text-muted small"><?= $db['username'] ? e($db['username']) : '—' ?></td>
                            <td class="text-muted small"><?= $db['app_name'] ? e($db['app_name']) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Domains ───────────────────────────────────────────────────────── -->
        <div class="tab-pane" id="tab-domains">
            <?php if (empty($domains)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-world fs-1 d-block mb-2"></i>
                No domains linked via applications on this server.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr><th>Domain</th><th>Registrar</th><th>Expires</th><th>SSL Expires</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($domains as $d): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/domains/' . $d['id']) ?>" class="fw-medium text-reset text-decoration-none">
                                    <?= e($d['root_domain']) ?>
                                </a>
                            </td>
                            <td class="text-muted small"><?= $d['registrar'] ? e($d['registrar']) : '—' ?></td>
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

        <!-- ── Credentials ───────────────────────────────────────────────────── -->
        <div class="tab-pane" id="tab-credentials">
            <?php if (!vault_unlocked()): ?>
            <div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
                <i class="ti ti-lock fs-3"></i>
                <div>Vault is locked — passwords masked.
                    <a href="<?= url('/vault/unlock') ?>">Unlock to reveal.</a></div>
            </div>
            <?php endif; ?>
            <?php if (empty($credentials)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-key fs-1 d-block mb-2"></i>
                No credentials linked to this server.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead><tr><th>Label</th><th>Type</th><th>Username</th><th>Added</th></tr></thead>
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
                            <td class="text-muted small" title="<?= e($cr['created_at']) ?>"><?= time_ago($cr['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Activity ──────────────────────────────────────────────────────── -->
        <div class="tab-pane" id="tab-activity">
            <?php if (empty($activity)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-activity fs-1 d-block mb-2"></i>
                No activity recorded for this server yet.
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($activity as $log): ?>
                <div class="list-group-item px-0 py-2">
                    <div class="d-flex align-items-start gap-3">
                        <span class="avatar avatar-xs bg-secondary-lt text-secondary mt-1">
                            <i class="ti ti-activity"></i>
                        </span>
                        <div class="flex-grow-1">
                            <div class="fw-medium small">
                                <?= $log['description'] ? e($log['description']) : e(str_replace('_', ' ', $log['action'])) ?>
                            </div>
                            <div class="text-muted" style="font-size:.75rem;">
                                <?php if ($log['username']): ?><i class="ti ti-user me-1"></i><?= e($log['username']) ?> &middot;<?php endif; ?>
                                <?php if ($log['ip_address']): ?><i class="ti ti-map-pin me-1"></i><?= e($log['ip_address']) ?> &middot;<?php endif; ?>
                                <?= time_ago($log['created_at']) ?>
                            </div>
                        </div>
                        <span class="text-muted ms-auto" style="font-size:.75rem;white-space:nowrap;">
                            <?= date('d M Y H:i', strtotime($log['created_at'])) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div><!-- /tab-content -->
</div><!-- /card -->

<!-- Hidden delete form -->
<form id="delete-form" method="post" action="" style="display:none;">
    <?= csrf_field() ?>
</form>

<script>
function confirmDelete(id, name) {
    if (!confirm('Delete server "' + name + '"?\n\nThis cannot be undone.')) return;
    const form = document.getElementById('delete-form');
    form.action = '<?= url('/servers/') ?>' + id + '/delete';
    form.submit();
}

function copyToClipboard(inputId, btn) {
    const val = document.getElementById(inputId).value;
    navigator.clipboard.writeText(val).then(() => {
        const icon = btn.querySelector('i');
        icon.className = 'ti ti-check text-success';
        setTimeout(() => icon.className = 'ti ti-copy', 1800);
    });
}
</script>

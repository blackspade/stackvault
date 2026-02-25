<?php
/**
 * Domain detail view — tabbed
 * Vars: $domain[], $dnsRecords[], $emailAccounts[], $applications[],
 *       $servers[], $databases[], $credentials[], $activity[]
 */

// Expiry days → inline color class
$expiryColor = function(?string $date): string {
    if (!$date) return 'text-muted';
    $days = (int) ((strtotime($date) - time()) / 86400);
    if ($days < 0)  return 'text-danger fw-bold';
    if ($days <= 7)  return 'text-danger fw-semibold';
    if ($days <= 30) return 'text-warning fw-semibold';
    return 'text-success';
};

$credTypeLabel = fn(string $t) => match($t) {
    'ssh'=>'SSH','cpanel'=>'cPanel','database'=>'DB','email'=>'Email',
    'api_key'=>'API Key','registrar'=>'Registrar','cloud'=>'Cloud',default=>'Other'
};
$credTypeColor = fn(string $t) => match($t) {
    'ssh'=>'cyan','cpanel'=>'orange','database'=>'indigo','email'=>'blue',
    'api_key'=>'purple','registrar'=>'teal','cloud'=>'azure',default=>'secondary'
};
$monStatus = fn(string $s) => match($s) {
    'online'  => '<span class="badge bg-success-lt text-success">Online</span>',
    'offline' => '<span class="badge bg-danger-lt text-danger">Offline</span>',
    'degraded'=> '<span class="badge bg-warning-lt text-warning">Degraded</span>',
    default   => '<span class="badge bg-secondary-lt text-muted">Unknown</span>',
};

$expiryDays = $domain['expiry_date']
    ? (int) ((strtotime($domain['expiry_date']) - time()) / 86400) : null;
$sslDays = $domain['ssl_expiry']
    ? (int) ((strtotime($domain['ssl_expiry']) - time()) / 86400) : null;
?>

<?php
// ── Page actions ──────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/domains/' . $domain['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="ti ti-edit me-1"></i>Edit
</a>
<button type="button" class="btn btn-sm btn-outline-danger ms-1"
        onclick="confirmDelete(<?= (int)$domain['id'] ?>, '<?= e(addslashes($domain['root_domain'])) ?>')">
    <i class="ti ti-trash me-1"></i>Delete
</button>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Domain header bar ──────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row align-items-center g-3">

            <div class="col-auto">
                <span class="avatar avatar-lg bg-cyan-lt text-cyan">
                    <i class="ti ti-world fs-2"></i>
                </span>
            </div>

            <div class="col">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h2 class="mb-0"><?= e($domain['root_domain']) ?></h2>
                    <?= $domain['is_active']
                        ? '<span class="badge bg-success-lt text-success">Active</span>'
                        : '<span class="badge bg-secondary-lt text-muted">Inactive</span>' ?>
                </div>
                <div class="d-flex flex-wrap gap-3 mt-1 text-muted small">
                    <?php if ($domain['registrar']): ?>
                    <span><i class="ti ti-building me-1"></i><?= e($domain['registrar']) ?></span>
                    <?php endif; ?>
                    <?php if ($domain['client_name']): ?>
                    <a href="<?= url('/clients/' . $domain['client_id']) ?>" class="text-muted">
                        <i class="ti ti-user me-1"></i><?= e($domain['client_name']) ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Expiry summaries -->
            <div class="col-auto d-none d-md-flex gap-4 text-center">
                <div>
                    <div class="h5 mb-0 <?= $expiryColor($domain['expiry_date']) ?>">
                        <?php if ($expiryDays === null): ?>
                            <span class="text-muted">—</span>
                        <?php elseif ($expiryDays < 0): ?>
                            Expired
                        <?php else: ?>
                            <?= $expiryDays ?>d
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small">Registration</div>
                </div>
                <div>
                    <div class="h5 mb-0 <?= $expiryColor($domain['ssl_expiry']) ?>">
                        <?php if ($sslDays === null): ?>
                            <span class="text-muted">—</span>
                        <?php elseif ($sslDays < 0): ?>
                            Expired
                        <?php else: ?>
                            <?= $sslDays ?>d
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small">SSL</div>
                </div>
                <div>
                    <div class="h3 mb-0"><?= count($dnsRecords) ?></div>
                    <div class="text-muted small">DNS</div>
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
                <a href="#tab-dns" class="nav-link" data-bs-toggle="tab">
                    <i class="ti ti-sitemap me-1"></i>DNS
                    <?php if (count($dnsRecords)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($dnsRecords) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="#tab-email" class="nav-link" data-bs-toggle="tab">
                    <i class="ti ti-mail me-1"></i>Email
                    <?php if (count($emailAccounts)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($emailAccounts) ?></span>
                    <?php endif; ?>
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
                <a href="#tab-servers" class="nav-link" data-bs-toggle="tab">
                    <i class="ti ti-server me-1"></i>Servers
                    <?php if (count($servers)): ?>
                    <span class="badge bg-blue text-white ms-1"><?= count($servers) ?></span>
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

                <div class="col-md-6">
                    <h4 class="mb-3">Domain Information</h4>
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted" style="width:160px;">Root Domain</td>
                            <td class="fw-medium"><?= e($domain['root_domain']) ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Client</td>
                            <td>
                                <?php if ($domain['client_name']): ?>
                                <a href="<?= url('/clients/' . $domain['client_id']) ?>">
                                    <?= e($domain['client_name']) ?>
                                </a>
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Registrar</td>
                            <td><?= $domain['registrar'] ? e($domain['registrar']) : '<span class="text-muted">—</span>' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Registration Expiry</td>
                            <td class="<?= $expiryColor($domain['expiry_date']) ?>">
                                <?php if ($domain['expiry_date']): ?>
                                    <?= e($domain['expiry_date']) ?>
                                    <?php if ($expiryDays !== null): ?>
                                    <span class="text-muted small ms-1">(<?= $expiryDays >= 0 ? $expiryDays . 'd left' : abs($expiryDays) . 'd ago' ?>)</span>
                                    <?php endif; ?>
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">SSL Expiry</td>
                            <td class="<?= $expiryColor($domain['ssl_expiry']) ?>">
                                <?php if ($domain['ssl_expiry']): ?>
                                    <?= e($domain['ssl_expiry']) ?>
                                    <?php if ($sslDays !== null): ?>
                                    <span class="text-muted small ms-1">(<?= $sslDays >= 0 ? $sslDays . 'd left' : abs($sslDays) . 'd ago' ?>)</span>
                                    <?php endif; ?>
                                <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Status</td>
                            <td>
                                <?= $domain['is_active']
                                    ? '<span class="badge bg-success-lt text-success">Active</span>'
                                    : '<span class="badge bg-secondary-lt text-muted">Inactive</span>' ?>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Added</td>
                            <td class="text-muted small"><?= e($domain['created_at']) ?></td>
                        </tr>
                    </table>
                </div>

                <div class="col-md-6">
                    <?php if ($domain['nameservers']): ?>
                    <h4 class="mb-3">Nameservers</h4>
                    <div class="bg-light rounded p-3">
                        <?php
                        $ns = preg_split('/[\r\n,]+/', $domain['nameservers']);
                        foreach (array_filter(array_map('trim', $ns)) as $n):
                        ?>
                        <code class="d-block"><?= e($n) ?></code>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($domain['notes']): ?>
                    <h4 class="mb-3 <?= $domain['nameservers'] ? 'mt-4' : '' ?>">Notes</h4>
                    <div class="bg-light rounded p-3" style="white-space:pre-wrap;font-size:.875rem;"><?= e($domain['notes']) ?></div>
                    <?php endif; ?>

                    <?php if (!$domain['nameservers'] && !$domain['notes']): ?>
                    <p class="text-muted mt-2">No nameservers or notes recorded.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>

        <!-- ── DNS Records ───────────────────────────────────────────────────── -->
        <div class="tab-pane" id="tab-dns">
            <div class="d-flex justify-content-end mb-3">
                <a href="<?= url('/dns/create?domain_id=' . $domain['id'] . '&return_to=' . urlencode('/domains/' . $domain['id'])) ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-plus me-1"></i>Add DNS Record
                </a>
            </div>
            <?php if (empty($dnsRecords)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-sitemap fs-1 d-block mb-2"></i>
                No DNS records yet for this domain.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm font-monospace">
                    <thead>
                        <tr>
                            <th style="width:80px">Type</th>
                            <th>Name</th>
                            <th>Value</th>
                            <th style="width:80px">TTL</th>
                            <th style="width:60px">Prio</th>
                            <th style="width:70px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dnsRecords as $r): ?>
                        <?php $dnsBadge = \App\Models\DnsRecordModel::typeBadgeClass($r['record_type']); ?>
                        <tr>
                            <td><span class="badge <?= $dnsBadge ?>"><?= e($r['record_type']) ?></span></td>
                            <td><?= e($r['name']) ?></td>
                            <td class="text-truncate" style="max-width:280px;" title="<?= e($r['value']) ?>"><?= e($r['value']) ?></td>
                            <td class="text-muted"><?= (int)$r['ttl'] ?></td>
                            <td class="text-muted"><?= $r['priority'] !== null ? (int)$r['priority'] : '—' ?></td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('/dns/' . $r['id']) ?>"
                                       class="btn btn-ghost-secondary" title="View">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a href="<?= url('/dns/' . $r['id'] . '/edit') ?>"
                                       class="btn btn-ghost-secondary" title="Edit">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Email Accounts ────────────────────────────────────────────────── -->
        <div class="tab-pane" id="tab-email">
            <div class="d-flex justify-content-end mb-3">
                <a href="<?= url('/email/create?domain_id=' . $domain['id'] . '&return_to=' . urlencode('/domains/' . $domain['id'])) ?>"
                   class="btn btn-sm btn-outline-primary">
                    <i class="ti ti-plus me-1"></i>Add Email Account
                </a>
            </div>
            <?php if (empty($emailAccounts)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-mail fs-1 d-block mb-2"></i>
                No email accounts linked to this domain yet.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr>
                            <th>Address</th>
                            <th>Mail Host</th>
                            <th style="width:60px" class="text-center">SMTP</th>
                            <th style="width:60px" class="text-center">IMAP</th>
                            <th style="width:80px">Webmail</th>
                            <th style="width:70px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($emailAccounts as $em): ?>
                        <tr>
                            <td class="fw-medium">
                                <a href="<?= url('/email/' . $em['id']) ?>" class="text-reset text-decoration-none">
                                    <?= e($em['email_address']) ?>
                                </a>
                            </td>
                            <td class="text-muted small font-monospace"><?= $em['mail_host'] ? e($em['mail_host']) : '—' ?></td>
                            <td class="text-center text-muted small"><?= $em['smtp_port'] ?? '—' ?></td>
                            <td class="text-center text-muted small"><?= $em['imap_port'] ?? '—' ?></td>
                            <td>
                                <?php if ($em['webmail_url']): ?>
                                <a href="<?= e($em['webmail_url']) ?>" target="_blank" rel="noopener" class="text-muted small">
                                    <i class="ti ti-external-link me-1"></i>Open
                                </a>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('/email/' . $em['id']) ?>"
                                       class="btn btn-ghost-secondary" title="View">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <a href="<?= url('/email/' . $em['id'] . '/edit') ?>"
                                       class="btn btn-ghost-secondary" title="Edit">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Applications ──────────────────────────────────────────────────── -->
        <div class="tab-pane" id="tab-apps">
            <?php if (empty($applications)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-apps fs-1 d-block mb-2"></i>
                No applications linked to this domain.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr><th>App</th><th>Version</th><th>Stack</th><th>Server</th><th>Deploy</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td class="fw-medium"><?= e($app['app_name']) ?></td>
                            <td class="text-muted small"><?= $app['version'] ? e($app['version']) : '—' ?></td>
                            <td class="text-muted small"><?= $app['stack_type'] ? e($app['stack_type']) : '—' ?></td>
                            <td class="text-muted small">
                                <?php if ($app['server_label']): ?>
                                <span><?= e($app['server_label']) ?></span>
                                <?php if ($app['server_ip']): ?><code class="ms-1 small"><?= e($app['server_ip']) ?></code><?php endif; ?>
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

        <!-- ── Hosting Servers ───────────────────────────────────────────────── -->
        <div class="tab-pane" id="tab-servers">
            <?php if (empty($servers)): ?>
            <div class="text-center text-muted py-5">
                <i class="ti ti-server fs-1 d-block mb-2"></i>
                No servers linked via applications on this domain.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr><th>Label</th><th>IP</th><th>Provider</th><th>OS</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($servers as $s): ?>
                        <tr>
                            <td class="fw-medium"><?= e($s['label']) ?></td>
                            <td><code><?= e($s['ip_address'] ?? '—') ?></code></td>
                            <td class="text-muted small"><?= $s['provider'] ? e($s['provider']) : '—' ?></td>
                            <td class="text-muted small"><?= $s['os_version'] ? e($s['os_version']) : '—' ?></td>
                            <td><?= $monStatus($s['monitoring_status']) ?></td>
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
                No databases linked via applications on this domain.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter table-sm">
                    <thead>
                        <tr><th>DB Name</th><th>Type</th><th>Host</th><th>Port</th><th>User</th><th>Application</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($databases as $db): ?>
                        <tr>
                            <td class="fw-medium"><?= e($db['db_name']) ?></td>
                            <td><span class="badge bg-indigo-lt text-indigo"><?= e(strtoupper($db['db_type'])) ?></span></td>
                            <td class="text-muted small"><?= e($db['host']) ?></td>
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
                No credentials linked to this domain.
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
                No activity recorded for this domain yet.
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
    if (!confirm('Delete domain "' + name + '"?\n\nThis cannot be undone.')) return;
    const form = document.getElementById('delete-form');
    form.action = '<?= url('/domains/') ?>' + id + '/delete';
    form.submit();
}
</script>

<?php
/**
 * Print-optimized client profile export
 *
 * Vars: $client[], $domains[], $servers[], $applications[],
 *       $databases[], $dnsRecords[], $emailAccounts[],
 *       $exportedAt (string), $appName (string)
 *
 * Uses the 'print' layout.
 */
?>

<!-- ── Document header ─────────────────────────────────────────────────────── -->
<div class="sv-print-header d-flex justify-content-between align-items-end">
    <div>
        <div class="brand d-flex align-items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                 fill="none" stroke="#206bc4" stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <?= e($appName) ?>
        </div>
        <div class="meta">Client Profile Export</div>
    </div>
    <div class="text-end meta">
        Generated: <?= e($exportedAt) ?>
    </div>
</div>

<!-- ── Client overview ─────────────────────────────────────────────────────── -->
<div class="sv-section">
    <div class="sv-section-title">Client Overview</div>
    <div class="row g-3">
        <div class="col-sm-6">
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <th class="text-muted fw-normal" style="width:130px">Client Name</th>
                    <td class="fw-semibold fs-5"><?= e($client['name']) ?></td>
                </tr>
                <?php if ($client['contact_name']): ?>
                <tr>
                    <th class="text-muted fw-normal">Contact</th>
                    <td><?= e($client['contact_name']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($client['contact_email']): ?>
                <tr>
                    <th class="text-muted fw-normal">Email</th>
                    <td><?= e($client['contact_email']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($client['contact_phone']): ?>
                <tr>
                    <th class="text-muted fw-normal">Phone</th>
                    <td><?= e($client['contact_phone']) ?></td>
                </tr>
                <?php endif; ?>
                <?php if ($client['website']): ?>
                <tr>
                    <th class="text-muted fw-normal">Website</th>
                    <td><?= e($client['website']) ?></td>
                </tr>
                <?php endif; ?>
                <tr>
                    <th class="text-muted fw-normal">Status</th>
                    <td><?= $client['is_active'] ? 'Active' : 'Inactive' ?></td>
                </tr>
                <tr>
                    <th class="text-muted fw-normal">Since</th>
                    <td class="text-muted"><?= e(substr((string) $client['created_at'], 0, 10)) ?></td>
                </tr>
            </table>
        </div>
        <?php if ($client['notes']): ?>
        <div class="col-sm-6">
            <div class="text-muted small fw-semibold mb-1">Notes</div>
            <div class="text-muted small" style="white-space:pre-wrap"><?= e($client['notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Domains ─────────────────────────────────────────────────────────────── -->
<div class="sv-section">
    <div class="sv-section-title">Domains (<?= count($domains) ?>)</div>
    <?php if (empty($domains)): ?>
    <p class="text-muted small fst-italic">No domains.</p>
    <?php else: ?>
    <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th>Domain</th>
                <th>Registrar</th>
                <th>Expiry</th>
                <th>SSL Expiry</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($domains as $d): ?>
        <tr>
            <td class="font-monospace fw-medium"><?= e($d['root_domain']) ?></td>
            <td><?= $d['registrar'] ? e($d['registrar']) : '—' ?></td>
            <td><?= $d['expiry_date'] ? e(substr((string) $d['expiry_date'], 0, 10)) : '—' ?></td>
            <td><?= $d['ssl_expiry']  ? e(substr((string) $d['ssl_expiry'],  0, 10)) : '—' ?></td>
            <td><?= $d['is_active'] ? 'Active' : 'Inactive' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── Servers ─────────────────────────────────────────────────────────────── -->
<div class="sv-section">
    <div class="sv-section-title">Servers (<?= count($servers) ?>)</div>
    <?php if (empty($servers)): ?>
    <p class="text-muted small fst-italic">No servers.</p>
    <?php else: ?>
    <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th>Label</th>
                <th>IP Address</th>
                <th>Hostname</th>
                <th>Provider</th>
                <th>OS</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($servers as $s): ?>
        <tr>
            <td class="fw-medium"><?= e($s['label']) ?></td>
            <td class="font-monospace"><?= $s['ip_address'] ? e($s['ip_address']) : '—' ?></td>
            <td class="text-muted small"><?= $s['hostname'] ? e($s['hostname']) : '—' ?></td>
            <td><?= $s['provider'] ? e($s['provider']) : '—' ?></td>
            <td class="text-muted small"><?= $s['os_version'] ? e($s['os_version']) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── Applications ────────────────────────────────────────────────────────── -->
<div class="sv-section">
    <div class="sv-section-title">Applications (<?= count($applications) ?>)</div>
    <?php if (empty($applications)): ?>
    <p class="text-muted small fst-italic">No applications.</p>
    <?php else: ?>
    <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th>Application</th>
                <th>Version</th>
                <th>Stack</th>
                <th>Server</th>
                <th>Deployment</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($applications as $a): ?>
        <tr>
            <td class="fw-medium"><?= e($a['app_name']) ?></td>
            <td><?= $a['version'] ? e($a['version']) : '—' ?></td>
            <td class="text-muted small"><?= $a['stack_type'] ? e($a['stack_type']) : '—' ?></td>
            <td><?= $a['server_label'] ? e($a['server_label']) : '—' ?></td>
            <td class="text-muted small"><?= $a['deployment_method'] ? e($a['deployment_method']) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── Databases ───────────────────────────────────────────────────────────── -->
<div class="sv-section">
    <div class="sv-section-title">Databases (<?= count($databases) ?>)</div>
    <?php if (empty($databases)): ?>
    <p class="text-muted small fst-italic">No databases.</p>
    <?php else: ?>
    <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th>Database Name</th>
                <th>Type</th>
                <th>Host</th>
                <th>Port</th>
                <th>Username</th>
                <th>Server</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($databases as $db): ?>
        <tr>
            <td class="font-monospace fw-medium"><?= e($db['db_name']) ?></td>
            <td><?= $db['db_type'] ? e($db['db_type']) : '—' ?></td>
            <td class="font-monospace text-muted small"><?= $db['host'] ? e($db['host']) : '—' ?></td>
            <td class="text-muted small"><?= $db['port'] ? (int) $db['port'] : '—' ?></td>
            <td class="font-monospace"><?= $db['username'] ? e($db['username']) : '—' ?></td>
            <td class="text-muted small"><?= $db['server_label'] ? e($db['server_label']) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ── DNS Records ─────────────────────────────────────────────────────────── -->
<?php if (!empty($dnsRecords)): ?>
<div class="sv-section page-break">
    <div class="sv-section-title">DNS Records (<?= count($dnsRecords) ?>)</div>
    <table class="table table-sm table-bordered mb-0 font-monospace">
        <thead class="table-light">
            <tr>
                <th style="width:60px">Type</th>
                <th style="width:140px">Domain</th>
                <th style="width:120px">Name</th>
                <th>Value</th>
                <th style="width:60px">TTL</th>
                <th style="width:50px">Prio</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($dnsRecords as $r): ?>
        <tr>
            <td><strong><?= e($r['record_type']) ?></strong></td>
            <td class="text-muted small"><?= e($r['root_domain']) ?></td>
            <td><?= e($r['name']) ?></td>
            <td class="small text-truncate" style="max-width:280px" title="<?= e($r['value']) ?>">
                <?= e($r['value']) ?>
            </td>
            <td class="text-muted small"><?= (int) $r['ttl'] ?></td>
            <td class="text-muted small"><?= $r['priority'] !== null ? (int) $r['priority'] : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ── Email Accounts ──────────────────────────────────────────────────────── -->
<?php if (!empty($emailAccounts)): ?>
<div class="sv-section">
    <div class="sv-section-title">Email Accounts (<?= count($emailAccounts) ?>)</div>
    <table class="table table-sm table-bordered mb-0">
        <thead class="table-light">
            <tr>
                <th>Email Address</th>
                <th>Domain</th>
                <th>Mail Host</th>
                <th>SMTP Port</th>
                <th>IMAP Port</th>
                <th>Username</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($emailAccounts as $e): ?>
        <tr>
            <td class="font-monospace fw-medium small"><?= e($e['email_address']) ?></td>
            <td class="text-muted small font-monospace"><?= $e['root_domain'] ? e($e['root_domain']) : '—' ?></td>
            <td class="font-monospace text-muted small"><?= $e['mail_host'] ? e($e['mail_host']) : '—' ?></td>
            <td class="text-muted small"><?= $e['smtp_port'] ? (int) $e['smtp_port'] : '—' ?></td>
            <td class="text-muted small"><?= $e['imap_port'] ? (int) $e['imap_port'] : '—' ?></td>
            <td class="font-monospace small"><?= $e['username'] ? e($e['username']) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ── Footer ──────────────────────────────────────────────────────────────── -->
<div class="text-muted small border-top pt-3 mt-4" style="font-size:11px">
    <?= e($appName) ?> — Confidential — Generated <?= e($exportedAt) ?>
</div>

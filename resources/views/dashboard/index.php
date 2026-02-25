<?php
/**
 * Dashboard — Stage 4
 * Stat cards, expiry alerts, recent credentials, OS breakdown, activity log.
 */

// Action icon map for activity log
$actionIcon = function(string $action): string {
    return match(true) {
        str_contains($action, 'login')      => 'ti-login',
        str_contains($action, 'logout')     => 'ti-logout',
        str_contains($action, 'create')     => 'ti-circle-plus',
        str_contains($action, 'update')     => 'ti-edit',
        str_contains($action, 'delete')     => 'ti-trash',
        str_contains($action, 'view')       => 'ti-eye',
        str_contains($action, 'failed')     => 'ti-alert-triangle',
        default                             => 'ti-activity',
    };
};

$actionColor = function(string $action): string {
    return match(true) {
        str_contains($action, 'failed')     => 'danger',
        str_contains($action, 'delete')     => 'warning',
        str_contains($action, 'create')     => 'success',
        str_contains($action, 'login')      => 'info',
        default                             => 'secondary',
    };
};

$credTypeLabel = function(string $type): string {
    return match($type) {
        'ssh'        => 'SSH',
        'cpanel'     => 'cPanel',
        'database'   => 'Database',
        'email'      => 'Email',
        'api_key'    => 'API Key',
        'registrar'  => 'Registrar',
        'cloud'      => 'Cloud',
        default      => 'Other',
    };
};

$credTypeColor = function(string $type): string {
    return match($type) {
        'ssh'        => 'cyan',
        'cpanel'     => 'orange',
        'database'   => 'indigo',
        'email'      => 'blue',
        'api_key'    => 'purple',
        'registrar'  => 'teal',
        'cloud'      => 'azure',
        default      => 'secondary',
    };
};

$urgencyClass = function(int $days): string {
    if ($days <= 7)  return 'text-danger fw-bold';
    if ($days <= 14) return 'text-orange fw-semibold';
    return 'text-warning';
};
?>

<?php /* ─── 2FA setup reminder ──────────────────────────────────────────────── */ ?>
<?php if (!empty($user['must_setup_2fa'])): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <i class="ti ti-shield-exclamation fs-3 flex-shrink-0"></i>
        <div>
            <strong>Action required: set up Two-Factor Authentication.</strong>
            Your account requires 2FA. Please configure it to secure your access.
        </div>
    </div>
    <a href="<?= url('/settings?tab=2fa') ?>" class="btn btn-warning btn-sm text-nowrap">
        <i class="ti ti-device-mobile-code me-1"></i>Set Up 2FA
    </a>
</div>
<?php endif; ?>

<?php /* ─── Vault status banner ────────────────────────────────────────────── */ ?>
<?php if (!vault_unlocked()): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <i class="ti ti-lock fs-3 flex-shrink-0"></i>
        <div>
            <strong>Vault is locked.</strong>
            Credential passwords are masked. Unlock the vault to view or copy them.
        </div>
    </div>
    <a href="<?= url('/vault/unlock') ?>" class="btn btn-warning btn-sm text-nowrap">
        <i class="ti ti-key me-1"></i>Unlock Vault
    </a>
</div>
<?php else: ?>
<div class="alert alert-success d-flex align-items-center justify-content-between gap-3 mb-4">
    <div class="d-flex align-items-center gap-2">
        <i class="ti ti-lock-open-2 fs-3 flex-shrink-0"></i>
        <div>
            <strong>Vault is unlocked.</strong>
            Credentials are accessible for this session.
        </div>
    </div>
    <form method="post" action="<?= url('/vault/lock') ?>">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-outline-success btn-sm text-nowrap">
            <i class="ti ti-lock me-1"></i>Lock Vault
        </button>
    </form>
</div>
<?php endif; ?>

<?php /* ─── Row 1: Stat Cards ────────────────────────────────────────────────── */ ?>
<div class="row row-deck row-cards mb-4">

    <?php
    $statCards = [
        ['label' => 'Clients',       'value' => $stats['clients'],        'icon' => 'ti-users',    'color' => 'blue'],
        ['label' => 'Servers',       'value' => $stats['servers'],        'icon' => 'ti-server',   'color' => 'indigo'],
        ['label' => 'Domains',       'value' => $stats['domains'],        'icon' => 'ti-world',    'color' => 'cyan'],
        ['label' => 'Credentials',   'value' => $stats['credentials'],    'icon' => 'ti-key',      'color' => 'purple'],
        ['label' => 'Databases',     'value' => $stats['db_instances'],   'icon' => 'ti-database', 'color' => 'orange'],
        ['label' => 'Email Accounts','value' => $stats['email_accounts'], 'icon' => 'ti-mail',     'color' => 'teal'],
    ];
    ?>

    <?php foreach ($statCards as $card): ?>
    <div class="col-sm-6 col-lg-2">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar avatar-sm bg-<?= e($card['color']) ?>-lt text-<?= e($card['color']) ?>">
                            <i class="ti <?= e($card['icon']) ?> fs-3"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium"><?= (int)$card['value'] ?></div>
                        <div class="text-muted small"><?= e($card['label']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

</div>

<?php /* ─── Row 2: Expiry Alert Banners ────────────────────────────────────── */ ?>
<?php
$domainCount = count($expiringDomains);
$sslCount    = count($expiringSsl);
if ($domainCount > 0 || $sslCount > 0): ?>
<div class="row row-cards mb-4">
    <?php if ($domainCount > 0): ?>
    <div class="col-md-6">
        <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
            <i class="ti ti-calendar-exclamation fs-3 flex-shrink-0"></i>
            <div>
                <strong><?= $domainCount ?> domain<?= $domainCount !== 1 ? 's' : '' ?></strong>
                expiring within 30 days. <a href="<?= url('/domains') ?>" class="alert-link">View domains &rarr;</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($sslCount > 0): ?>
    <div class="col-md-6">
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-0">
            <i class="ti ti-certificate fs-3 flex-shrink-0"></i>
            <div>
                <strong><?= $sslCount ?> SSL certificate<?= $sslCount !== 1 ? 's' : '' ?></strong>
                expiring within 30 days. <a href="<?= url('/domains') ?>" class="alert-link">Review now &rarr;</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php /* ─── Row 3: Expiring Domains + SSL Tables ─────────────────────────── */ ?>
<div class="row row-cards mb-4">

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-calendar-due me-1 text-warning"></i>
                    Domains Expiring (30 days)
                </h3>
                <?php if ($domainCount > 0): ?>
                <div class="card-options">
                    <span class="badge bg-warning text-white"><?= $domainCount ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm">
                    <?php if (empty($expiringDomains)): ?>
                    <tbody>
                        <tr>
                            <td class="text-muted text-center py-4">
                                <i class="ti ti-circle-check text-success me-1"></i>
                                No domains expiring within 30 days
                            </td>
                        </tr>
                    </tbody>
                    <?php else: ?>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Client</th>
                            <th class="text-end">Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiringDomains as $d): ?>
                        <tr>
                            <td>
                                <div class="fw-medium"><?= e($d['root_domain']) ?></div>
                                <?php if ($d['registrar']): ?>
                                <div class="text-muted small"><?= e($d['registrar']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= $d['client_name'] ? e($d['client_name']) : '—' ?></td>
                            <td class="text-end">
                                <span class="<?= $urgencyClass((int)$d['days_left']) ?>">
                                    <?= (int)$d['days_left'] ?>d
                                </span>
                                <div class="text-muted small"><?= e($d['expiry_date']) ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-certificate me-1 text-danger"></i>
                    SSL Expiring (30 days)
                </h3>
                <?php if ($sslCount > 0): ?>
                <div class="card-options">
                    <span class="badge bg-danger text-white"><?= $sslCount ?></span>
                </div>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm">
                    <?php if (empty($expiringSsl)): ?>
                    <tbody>
                        <tr>
                            <td class="text-muted text-center py-4">
                                <i class="ti ti-shield-check text-success me-1"></i>
                                No SSL certificates expiring within 30 days
                            </td>
                        </tr>
                    </tbody>
                    <?php else: ?>
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Client</th>
                            <th class="text-end">SSL Expires</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($expiringSsl as $d): ?>
                        <tr>
                            <td class="fw-medium"><?= e($d['root_domain']) ?></td>
                            <td class="text-muted"><?= $d['client_name'] ? e($d['client_name']) : '—' ?></td>
                            <td class="text-end">
                                <span class="<?= $urgencyClass((int)$d['days_left']) ?>">
                                    <?= (int)$d['days_left'] ?>d
                                </span>
                                <div class="text-muted small"><?= e($d['ssl_expiry']) ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

</div>

<?php /* ─── Row 3b: Reminders Widget ──────────────────────────────────────── */ ?>
<?php if (!empty($upcomingReminders)): ?>
<?php
$overdueReminders  = array_filter($upcomingReminders, fn($r) => (int) $r['days_until'] < 0);
$todayReminders    = array_filter($upcomingReminders, fn($r) => (int) $r['days_until'] === 0);
$upcomingOnly      = array_filter($upcomingReminders, fn($r) => (int) $r['days_until'] > 0);
$overdueCount      = count($overdueReminders);
?>
<div class="row row-cards mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-bell me-1 text-warning"></i>
                    Reminders
                </h3>
                <div class="card-options d-flex gap-2">
                    <?php if ($overdueCount > 0): ?>
                    <span class="badge bg-danger"><?= $overdueCount ?> overdue</span>
                    <?php endif; ?>
                    <a href="<?= url('/reminders/create') ?>" class="btn btn-sm btn-ghost-secondary">
                        <i class="ti ti-plus me-1"></i>Add
                    </a>
                    <a href="<?= url('/reminders') ?>" class="btn btn-sm btn-ghost-secondary">View all</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm">
                    <tbody>
                    <?php foreach ($upcomingReminders as $rem): ?>
                    <?php
                        $days = (int) $rem['days_until'];
                        if ($days < 0) {
                            $dueStr   = abs($days) . 'd overdue';
                            $dueClass = 'text-danger fw-bold';
                        } elseif ($days === 0) {
                            $dueStr   = 'Today';
                            $dueClass = 'text-danger fw-bold';
                        } elseif ($days <= 7) {
                            $dueStr   = 'In ' . $days . 'd';
                            $dueClass = 'text-orange fw-semibold';
                        } else {
                            $dueStr   = 'In ' . $days . 'd';
                            $dueClass = 'text-muted';
                        }
                        $typeColors = \App\Models\ReminderModel::TYPE_COLORS;
                        $typeIcons  = \App\Models\ReminderModel::TYPE_ICONS;
                        $typeKey    = $rem['type'] ?? 'custom';
                        $tc = $typeColors[$typeKey] ?? 'secondary';
                        $ti = $typeIcons[$typeKey]  ?? 'ti-bell';
                    ?>
                    <tr>
                        <td style="width:100px">
                            <span class="<?= $dueClass ?>"><?= $dueStr ?></span>
                            <div class="text-muted small"><?= e($rem['due_date']) ?></div>
                        </td>
                        <td>
                            <div class="fw-medium"><?= e($rem['title']) ?></div>
                            <?php if ($rem['client_name']): ?>
                            <div class="text-muted small"><?= e($rem['client_name']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="width:160px">
                            <span class="badge bg-<?= $tc ?>-lt text-<?= $tc ?>">
                                <i class="ti <?= $ti ?> me-1"></i>
                                <?= e(\App\Models\ReminderModel::TYPES[$typeKey] ?? $typeKey) ?>
                            </span>
                        </td>
                        <td style="width:80px">
                            <form method="post" action="<?= url('/reminders/' . $rem['id'] . '/done') ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-ghost-success" title="Mark done">
                                    <i class="ti ti-circle-check"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php /* ─── Row 4: Recent Credentials + Server OS Breakdown ─────────────── */ ?>
<div class="row row-cards mb-4">

    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-key me-1 text-purple"></i>
                    Recently Added Credentials
                </h3>
                <div class="card-options">
                    <a href="<?= url('/credentials') ?>" class="btn btn-sm btn-ghost-secondary">View all</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm">
                    <?php if (empty($recentCreds)): ?>
                    <tbody>
                        <tr>
                            <td class="text-muted text-center py-4">No credentials stored yet</td>
                        </tr>
                    </tbody>
                    <?php else: ?>
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Client</th>
                            <th class="text-end">Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentCreds as $cr): ?>
                        <tr>
                            <td class="fw-medium"><?= e($cr['label']) ?></td>
                            <td>
                                <span class="badge bg-<?= $credTypeColor($cr['credential_type']) ?>-lt text-<?= $credTypeColor($cr['credential_type']) ?>">
                                    <?= $credTypeLabel($cr['credential_type']) ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= $cr['client_name'] ? e($cr['client_name']) : '—' ?></td>
                            <td class="text-end text-muted small" title="<?= e($cr['created_at']) ?>">
                                <?= time_ago($cr['created_at']) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-server me-1 text-indigo"></i>
                    Server OS Distribution
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($osBreakdown)): ?>
                <div class="text-muted text-center py-4">No servers recorded yet</div>
                <?php else: ?>
                <?php
                $total = array_sum(array_column($osBreakdown, 'count'));
                $palette = ['blue','indigo','purple','pink','red','orange','yellow','cyan'];
                ?>
                <?php foreach ($osBreakdown as $i => $row): ?>
                <?php
                $pct   = $total > 0 ? round(($row['count'] / $total) * 100) : 0;
                $color = $palette[$i % count($palette)];
                ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-medium small"><?= e($row['os']) ?></span>
                        <span class="text-muted small"><?= $row['count'] ?> (<?= $pct ?>%)</span>
                    </div>
                    <div class="progress progress-sm">
                        <div class="progress-bar bg-<?= $color ?>" style="width: <?= $pct ?>%"
                             role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php /* ─── Row 5: Recent Activity + Failed Logins (tabbed) ──────────────── */ ?>
<div class="row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                    <li class="nav-item">
                        <a href="#tab-activity" class="nav-link active" data-bs-toggle="tab">
                            <i class="ti ti-activity me-1"></i>Recent Activity
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#tab-failed" class="nav-link" data-bs-toggle="tab">
                            <i class="ti ti-shield-lock me-1"></i>Failed Logins
                            <?php if ($failedCount24h > 0): ?>
                            <span class="badge bg-danger ms-1"><?= $failedCount24h ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
                <div class="card-options">
                    <a href="<?= url('/settings?tab=logs') ?>" class="btn btn-sm btn-ghost-secondary">Full log</a>
                </div>
            </div>

            <div class="tab-content">

                <!-- ── Recent Activity tab ─────────────────────────────── -->
                <div class="tab-pane active show" id="tab-activity">
                    <div class="card-body p-0">
                        <?php if (empty($recentActivity)): ?>
                        <div class="text-muted text-center py-4">No activity recorded yet</div>
                        <?php else: ?>
                        <div class="list-group list-group-flush overflow-hidden">
                            <?php foreach ($recentActivity as $log): ?>
                            <div class="list-group-item px-3 py-2">
                                <div class="row align-items-center g-2">
                                    <div class="col-auto">
                                        <span class="avatar avatar-xs bg-<?= $actionColor($log['action']) ?>-lt text-<?= $actionColor($log['action']) ?>">
                                            <i class="ti <?= $actionIcon($log['action']) ?>"></i>
                                        </span>
                                    </div>
                                    <div class="col text-truncate">
                                        <div class="fw-medium small text-truncate">
                                            <?= $log['description'] ? e($log['description']) : e(str_replace('_', ' ', $log['action'])) ?>
                                        </div>
                                        <div class="text-muted" style="font-size:0.75rem">
                                            <?php if ($log['username']): ?>
                                            <i class="ti ti-user me-1"></i><?= e($log['username']) ?> &middot;
                                            <?php endif; ?>
                                            <?php if ($log['ip_address']): ?>
                                            <i class="ti ti-map-pin me-1"></i><?= e($log['ip_address']) ?> &middot;
                                            <?php endif; ?>
                                            <?= time_ago($log['created_at']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ── Failed Logins tab ───────────────────────────────── -->
                <div class="tab-pane" id="tab-failed">
                    <div class="card-body p-0">
                        <?php if (empty($failedLogins)): ?>
                        <div class="text-muted text-center py-4">
                            <i class="ti ti-shield-check text-success me-1"></i>
                            No failed logins recorded
                        </div>
                        <?php else: ?>
                        <div class="list-group list-group-flush overflow-hidden">
                            <?php foreach ($failedLogins as $fl): ?>
                            <div class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="text-danger small fw-medium">
                                            <?= $fl['description'] ? e($fl['description']) : 'Failed login attempt' ?>
                                        </div>
                                        <?php if ($fl['ip_address']): ?>
                                        <div class="text-muted" style="font-size:0.75rem">
                                            <i class="ti ti-map-pin me-1"></i><?= e($fl['ip_address']) ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-muted ms-2 text-nowrap" style="font-size:0.75rem"
                                          title="<?= e($fl['created_at']) ?>">
                                        <?= time_ago($fl['created_at']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /tab-content -->
        </div>
    </div>
</div>

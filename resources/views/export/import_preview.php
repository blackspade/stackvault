<?php
/**
 * Vars: $exportedAt (string), $version (int), $preview[] (table => ['incoming' => n, 'existing' => n])
 */

$totalIncoming = array_sum(array_column($preview, 'incoming'));
$tableLabels   = [
    'clients'       => 'Clients',
    'servers'       => 'Servers',
    'domains'       => 'Domains',
    'credentials'   => 'Credentials',
    'applications'  => 'Applications',
    'db_instances'  => 'Databases',
    'dns_records'   => 'DNS Records',
    'email_accounts'=> 'Email Accounts',
];
?>

<!-- ── Summary ─────────────────────────────────────────────────────────────── -->
<div class="alert alert-info mb-4">
    <div class="d-flex gap-2">
        <i class="ti ti-info-circle fs-4 flex-shrink-0"></i>
        <div>
            Backup from <strong><?= e($exportedAt) ?></strong>
            (format version <?= (int) $version ?>).
            Contains <strong><?= $totalIncoming ?></strong> total record(s) across all tables.
            Existing records (matched by ID) will be skipped.
        </div>
    </div>
</div>

<!-- ── Preview table ───────────────────────────────────────────────────────── -->
<div class="card mb-4">
    <div class="card-header"><h4 class="card-title">What Will Be Imported</h4></div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table mb-0">
            <thead>
                <tr>
                    <th>Table</th>
                    <th class="text-end">Records in Backup</th>
                    <th class="text-end">Currently in DB</th>
                    <th class="text-end">Will Be Inserted</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($preview as $table => $counts):
                $label    = $tableLabels[$table] ?? $table;
                $incoming = (int) $counts['incoming'];
                $existing = (int) $counts['existing'];
                // Actual inserts = incoming (INSERT IGNORE skips dupes at DB level)
            ?>
            <tr>
                <td class="fw-medium"><?= e($label) ?></td>
                <td class="text-end">
                    <?php if ($incoming > 0): ?>
                    <span class="badge bg-blue-lt text-blue"><?= $incoming ?></span>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end text-muted"><?= $existing ?></td>
                <td class="text-end">
                    <?php if ($incoming === 0): ?>
                    <span class="text-muted small">Nothing to import</span>
                    <?php elseif ($existing === 0): ?>
                    <span class="badge bg-green-lt text-green">Up to <?= $incoming ?> new</span>
                    <?php else: ?>
                    <span class="badge bg-yellow-lt text-yellow">Up to <?= $incoming ?> (dupes skipped)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── Warning + Confirm ───────────────────────────────────────────────────── -->
<div class="card border-warning mb-4">
    <div class="card-body py-2 text-warning fw-medium">
        <i class="ti ti-alert-triangle me-1"></i>
        Review the table above before proceeding. This action cannot be undone.
        Credentials in the backup will only be readable if they were encrypted with the same vault key.
    </div>
</div>

<form method="post" action="<?= url('/export/import/confirm') ?>">
    <?= csrf_field() ?>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">
            <i class="ti ti-check me-1"></i>Confirm Import
        </button>
        <a href="<?= url('/export/import') ?>" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Cancel
        </a>
    </div>
</form>

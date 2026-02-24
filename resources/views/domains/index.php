<?php
/**
 * Domains index
 * Vars: $domains[], $search
 */

// Expiry badge: returns [cssClass, label]
$expiryBadge = function(?int $days, ?string $date): string {
    if ($date === null || $date === '') {
        return '<span class="badge bg-secondary-lt text-muted">—</span>';
    }
    if ($days === null) {
        return '<span class="badge bg-secondary-lt text-muted">' . e($date) . '</span>';
    }
    if ($days < 0) {
        return '<span class="badge bg-danger text-white" title="' . e($date) . '">Expired</span>';
    }
    if ($days <= 7) {
        return '<span class="badge bg-danger-lt text-danger" title="' . e($date) . '">' . $days . 'd left</span>';
    }
    if ($days <= 30) {
        return '<span class="badge bg-warning-lt text-warning" title="' . e($date) . '">' . $days . 'd left</span>';
    }
    return '<span class="badge bg-success-lt text-success" title="' . e($date) . '">' . $days . 'd left</span>';
};

// Count expiring soon for header badge
$expiringCount = count(array_filter($domains, fn($d) =>
    isset($d['expiry_days_left']) && (int)$d['expiry_days_left'] <= 30
    && (int)$d['expiry_days_left'] >= 0
));
$sslExpiringCount = count(array_filter($domains, fn($d) =>
    isset($d['ssl_days_left']) && (int)$d['ssl_days_left'] <= 30
    && (int)$d['ssl_days_left'] >= 0
));
?>

<div class="card">

    <!-- Toolbar -->
    <div class="card-header d-flex align-items-center gap-3 flex-wrap">
        <form method="get" action="<?= url('/domains') ?>" class="me-auto d-flex gap-2" style="max-width:380px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="search" name="search" class="form-control"
                       placeholder="Search domain, registrar, client…"
                       value="<?= e($search) ?>">
            </div>
            <?php if ($search !== ''): ?>
            <a href="<?= url('/domains') ?>" class="btn btn-sm btn-outline-secondary" title="Clear">
                <i class="ti ti-x"></i>
            </a>
            <?php endif; ?>
        </form>

        <?php if ($expiringCount > 0): ?>
        <span class="badge bg-warning-lt text-warning" title="Registration expiring within 30 days">
            <i class="ti ti-calendar-exclamation me-1"></i><?= $expiringCount ?> expiring
        </span>
        <?php endif; ?>
        <?php if ($sslExpiringCount > 0): ?>
        <span class="badge bg-danger-lt text-danger" title="SSL expiring within 30 days">
            <i class="ti ti-certificate me-1"></i><?= $sslExpiringCount ?> SSL expiring
        </span>
        <?php endif; ?>

        <a href="<?= url('/domains/create') ?>" class="btn btn-primary btn-sm">
            <i class="ti ti-circle-plus me-1"></i>Add Domain
        </a>
    </div>

    <?php if (empty($domains)): ?>
    <div class="card-body text-center py-5 text-muted">
        <?php if ($search !== ''): ?>
            <i class="ti ti-search-off fs-1 d-block mb-2"></i>
            No domains match "<strong><?= e($search) ?></strong>".
            <br><a href="<?= url('/domains') ?>" class="mt-2 d-inline-block">Clear search</a>
        <?php else: ?>
            <i class="ti ti-world fs-1 d-block mb-2"></i>
            No domains yet. <a href="<?= url('/domains/create') ?>">Add your first domain.</a>
        <?php endif; ?>
    </div>
    <?php else: ?>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Client</th>
                    <th>Registrar</th>
                    <th>Registration</th>
                    <th>SSL</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($domains as $d): ?>
                <tr>
                    <td>
                        <a href="<?= url('/domains/' . $d['id']) ?>"
                           class="fw-medium text-reset text-decoration-none">
                            <?= e($d['root_domain']) ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($d['client_name']): ?>
                        <a href="<?= url('/clients/' . $d['client_id']) ?>"
                           class="text-muted small text-decoration-none">
                            <?= e($d['client_name']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small">
                        <?= $d['registrar'] ? e($d['registrar']) : '—' ?>
                    </td>
                    <td>
                        <?= $expiryBadge(
                            isset($d['expiry_days_left']) ? (int)$d['expiry_days_left'] : null,
                            $d['expiry_date'] ?? null
                        ) ?>
                    </td>
                    <td>
                        <?= $expiryBadge(
                            isset($d['ssl_days_left']) ? (int)$d['ssl_days_left'] : null,
                            $d['ssl_expiry'] ?? null
                        ) ?>
                    </td>
                    <td>
                        <?= $d['is_active']
                            ? '<span class="badge bg-success-lt text-success">Active</span>'
                            : '<span class="badge bg-secondary-lt text-muted">Inactive</span>' ?>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('/domains/' . $d['id']) ?>"
                               class="btn btn-outline-secondary" title="View">
                                <i class="ti ti-eye"></i>
                            </a>
                            <a href="<?= url('/domains/' . $d['id'] . '/edit') ?>"
                               class="btn btn-outline-secondary" title="Edit">
                                <i class="ti ti-edit"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger" title="Delete"
                                    onclick="confirmDelete(<?= (int)$d['id'] ?>, '<?= e(addslashes($d['root_domain'])) ?>')">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer text-muted small">
        <?= count($domains) ?> domain<?= count($domains) !== 1 ? 's' : '' ?>
        <?= $search !== '' ? ' matching "' . e($search) . '"' : '' ?>
    </div>

    <?php endif; ?>
</div>

<form id="delete-form" method="post" action="" style="display:none;">
    <?= csrf_field() ?>
</form>

<script>
function confirmDelete(id, name) {
    if (!confirm('Delete domain "' + name + '"?\n\nThis will also remove linked DNS records and email accounts.')) return;
    const form = document.getElementById('delete-form');
    form.action = '<?= url('/domains/') ?>' + id + '/delete';
    form.submit();
}
</script>

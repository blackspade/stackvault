<?php
/**
 * Clients index
 * Vars: $clients[], $search
 */
$monitoringBadge = function(string $status): string {
    return match($status) {
        'online'   => '<span class="badge bg-success-lt text-success">Online</span>',
        'offline'  => '<span class="badge bg-danger-lt text-danger">Offline</span>',
        'degraded' => '<span class="badge bg-warning-lt text-warning">Degraded</span>',
        default    => '<span class="badge bg-secondary-lt text-muted">Unknown</span>',
    };
};
?>

<div class="card">

    <!-- Toolbar -->
    <div class="card-header d-flex align-items-center gap-3">
        <form method="get" action="<?= url('/clients') ?>" class="me-auto d-flex gap-2" style="max-width:360px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="search" name="search" class="form-control"
                       placeholder="Search by name, email, contact…"
                       value="<?= e($search) ?>">
            </div>
            <?php if ($search !== ''): ?>
            <a href="<?= url('/clients') ?>" class="btn btn-sm btn-outline-secondary" title="Clear search">
                <i class="ti ti-x"></i>
            </a>
            <?php endif; ?>
        </form>

        <a href="<?= url('/clients/create') ?>" class="btn btn-primary btn-sm">
            <i class="ti ti-circle-plus me-1"></i>Add Client
        </a>
    </div>

    <?php if (empty($clients)): ?>
    <div class="card-body text-center py-5 text-muted">
        <?php if ($search !== ''): ?>
            <i class="ti ti-search-off fs-1 d-block mb-2"></i>
            No clients match "<strong><?= e($search) ?></strong>".
            <br><a href="<?= url('/clients') ?>" class="mt-2 d-inline-block">Clear search</a>
        <?php else: ?>
            <i class="ti ti-users fs-1 d-block mb-2"></i>
            No clients yet. <a href="<?= url('/clients/create') ?>">Add your first client.</a>
        <?php endif; ?>
    </div>
    <?php else: ?>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Contact</th>
                    <th class="text-center" title="Domains">
                        <i class="ti ti-world"></i>
                    </th>
                    <th class="text-center" title="Servers">
                        <i class="ti ti-server"></i>
                    </th>
                    <th class="text-center" title="Credentials">
                        <i class="ti ti-key"></i>
                    </th>
                    <th>Status</th>
                    <th>Added</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clients as $c): ?>
                <tr>
                    <td>
                        <a href="<?= url('/clients/' . $c['id']) ?>" class="fw-medium text-reset text-decoration-none">
                            <?= e($c['name']) ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($c['contact_name']): ?>
                        <div class="small"><?= e($c['contact_name']) ?></div>
                        <?php endif; ?>
                        <?php if ($c['contact_email']): ?>
                        <a href="mailto:<?= e($c['contact_email']) ?>"
                           class="text-muted small"><?= e($c['contact_email']) ?></a>
                        <?php endif; ?>
                        <?php if (!$c['contact_name'] && !$c['contact_email']): ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <span class="text-muted small"><?= (int)$c['domain_count'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="text-muted small"><?= (int)$c['server_count'] ?></span>
                    </td>
                    <td class="text-center">
                        <span class="text-muted small"><?= (int)$c['cred_count'] ?></span>
                    </td>
                    <td>
                        <?php if ($c['is_active']): ?>
                        <span class="badge bg-success-lt text-success">Active</span>
                        <?php else: ?>
                        <span class="badge bg-secondary-lt text-muted">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small" title="<?= e($c['created_at']) ?>">
                        <?= date('d M Y', strtotime($c['created_at'])) ?>
                    </td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('/clients/' . $c['id']) ?>"
                               class="btn btn-outline-secondary" title="View">
                                <i class="ti ti-eye"></i>
                            </a>
                            <a href="<?= url('/clients/' . $c['id'] . '/edit') ?>"
                               class="btn btn-outline-secondary" title="Edit">
                                <i class="ti ti-edit"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger"
                                    title="Delete"
                                    onclick="confirmDelete(<?= (int)$c['id'] ?>, '<?= e(addslashes($c['name'])) ?>')">
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
        <?= count($clients) ?> client<?= count($clients) !== 1 ? 's' : '' ?>
        <?= $search !== '' ? ' matching "' . e($search) . '"' : '' ?>
    </div>

    <?php endif; ?>
</div>

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

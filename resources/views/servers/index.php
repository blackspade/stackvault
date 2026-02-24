<?php
/**
 * Servers index
 * Vars: $servers[], $search
 */
$monBadge = fn(string $s) => match($s) {
    'online'   => '<span class="badge bg-success">Online</span>',
    'offline'  => '<span class="badge bg-danger">Offline</span>',
    'degraded' => '<span class="badge bg-warning">Degraded</span>',
    default    => '<span class="badge bg-secondary-lt text-muted">Unknown</span>',
};

$onlineCount  = count(array_filter($servers, fn($s) => $s['monitoring_status'] === 'online'));
$offlineCount = count(array_filter($servers, fn($s) => $s['monitoring_status'] === 'offline'));
$degradedCount= count(array_filter($servers, fn($s) => $s['monitoring_status'] === 'degraded'));
?>

<div class="card">

    <!-- Toolbar -->
    <div class="card-header d-flex align-items-center gap-3 flex-wrap">
        <form method="get" action="<?= url('/servers') ?>" class="me-auto d-flex gap-2" style="max-width:380px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="search" name="search" class="form-control"
                       placeholder="Search label, IP, provider, OS…"
                       value="<?= e($search) ?>">
            </div>
            <?php if ($search !== ''): ?>
            <a href="<?= url('/servers') ?>" class="btn btn-sm btn-outline-secondary" title="Clear">
                <i class="ti ti-x"></i>
            </a>
            <?php endif; ?>
        </form>

        <!-- Status summary chips -->
        <?php if ($onlineCount):  ?><span class="badge bg-success"><?= $onlineCount ?> online</span><?php endif; ?>
        <?php if ($offlineCount): ?><span class="badge bg-danger"><?= $offlineCount ?> offline</span><?php endif; ?>
        <?php if ($degradedCount):?><span class="badge bg-warning"><?= $degradedCount ?> degraded</span><?php endif; ?>

        <a href="<?= url('/servers/create') ?>" class="btn btn-primary btn-sm">
            <i class="ti ti-circle-plus me-1"></i>Add Server
        </a>
    </div>

    <?php if (empty($servers)): ?>
    <div class="card-body text-center py-5 text-muted">
        <?php if ($search !== ''): ?>
            <i class="ti ti-search-off fs-1 d-block mb-2"></i>
            No servers match "<strong><?= e($search) ?></strong>".
            <br><a href="<?= url('/servers') ?>">Clear search</a>
        <?php else: ?>
            <i class="ti ti-server fs-1 d-block mb-2"></i>
            No servers yet. <a href="<?= url('/servers/create') ?>">Add your first server.</a>
        <?php endif; ?>
    </div>
    <?php else: ?>

    <div class="table-responsive">
        <table class="table table-vcenter table-hover card-table">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>IP / Hostname</th>
                    <th>Provider</th>
                    <th>OS</th>
                    <th>Client</th>
                    <th class="text-center">Apps</th>
                    <th class="text-center">DBs</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($servers as $s): ?>
                <tr>
                    <td>
                        <a href="<?= url('/servers/' . $s['id']) ?>"
                           class="fw-medium text-reset text-decoration-none">
                            <?= e($s['label']) ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($s['ip_address']): ?>
                        <code class="small"><?= e($s['ip_address']) ?></code>
                        <?php endif; ?>
                        <?php if ($s['hostname']): ?>
                        <div class="text-muted small"><?= e($s['hostname']) ?></div>
                        <?php endif; ?>
                        <?php if (!$s['ip_address'] && !$s['hostname']): ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= $s['provider']   ? e($s['provider'])   : '—' ?></td>
                    <td class="text-muted small"><?= $s['os_version'] ? e($s['os_version']) : '—' ?></td>
                    <td>
                        <?php if ($s['client_name']): ?>
                        <a href="<?= url('/clients/' . $s['client_id']) ?>"
                           class="text-muted small text-decoration-none">
                            <?= e($s['client_name']) ?>
                        </a>
                        <?php else: ?>
                        <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-muted small"><?= (int)$s['app_count'] ?></td>
                    <td class="text-center text-muted small"><?= (int)$s['db_count']  ?></td>
                    <td><?= $monBadge($s['monitoring_status']) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="<?= url('/servers/' . $s['id']) ?>"
                               class="btn btn-outline-secondary" title="View">
                                <i class="ti ti-eye"></i>
                            </a>
                            <a href="<?= url('/servers/' . $s['id'] . '/edit') ?>"
                               class="btn btn-outline-secondary" title="Edit">
                                <i class="ti ti-edit"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger" title="Delete"
                                    onclick="confirmDelete(<?= (int)$s['id'] ?>, '<?= e(addslashes($s['label'])) ?>')">
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
        <?= count($servers) ?> server<?= count($servers) !== 1 ? 's' : '' ?>
        <?= $search !== '' ? ' matching "' . e($search) . '"' : '' ?>
    </div>

    <?php endif; ?>
</div>

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
</script>

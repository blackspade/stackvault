<?php
/**
 * Vars: $apps[], $search
 */

// ── Page actions ──────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/app-catalog') ?>" class="btn btn-outline-secondary d-none d-sm-inline-flex">
    <i class="ti ti-layout-grid me-1"></i>App Catalog
</a>
<a href="<?= url('/applications/create') ?>" class="btn btn-primary d-none d-sm-inline-flex">
    <i class="ti ti-plus me-1"></i>Add Application
</a>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Search bar ─────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= url('/applications') ?>" class="d-flex gap-2 align-items-center">
            <input type="text" name="search" class="form-control"
                   placeholder="Search name, stack, client, server…"
                   value="<?= e($search) ?>">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-search me-1"></i>Search
            </button>
            <?php if ($search): ?>
            <a href="<?= url('/applications') ?>" class="btn btn-outline-secondary" title="Clear">
                <i class="ti ti-x"></i>
            </a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ── Applications table ─────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?= count($apps) ?> application<?= count($apps) !== 1 ? 's' : '' ?>
            <?php if ($search): ?>
            <span class="badge bg-blue-lt text-blue ms-2">filtered</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($apps)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-apps fs-1 d-block mb-2 opacity-50"></i>
        <?php if ($search): ?>
            No applications match your search.
        <?php else: ?>
            No applications yet.
            <a href="<?= url('/applications/create') ?>">Add your first application</a>
            or <a href="<?= url('/app-catalog') ?>">browse the catalog</a>.
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th style="width:52px"></th>
                    <th>Application</th>
                    <th>Stack / Tech</th>
                    <th>Client</th>
                    <th>Server</th>
                    <th style="width:90px">Creds</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($apps as $app): ?>
            <tr>
                <td class="text-center">
                    <img src="<?= url('/app-icon/' . rawurlencode($app['app_name'])) ?>"
                         width="36" height="36" class="rounded"
                         alt=""
                         onerror="this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQI12NgAAIABQAABjkB6QAAAABJRU5ErkJggg=='">
                </td>
                <td>
                    <a href="<?= url('/applications/' . $app['id']) ?>"
                       class="fw-medium text-reset text-decoration-none">
                        <?= e($app['app_name']) ?>
                    </a>
                    <?php if ($app['version']): ?>
                    <span class="text-muted small ms-1">v<?= e($app['version']) ?></span>
                    <?php endif; ?>
                    <?php if ($app['domain_name']): ?>
                    <br><span class="text-muted small">
                        <i class="ti ti-world me-1"></i><?= e($app['domain_name']) ?>
                    </span>
                    <?php endif; ?>
                </td>
                <td class="text-muted small">
                    <?= $app['stack_type'] ? e($app['stack_type']) : '—' ?>
                </td>
                <td class="text-muted small">
                    <?php if ($app['client_name']): ?>
                    <a href="<?= url('/clients/' . $app['client_id']) ?>" class="text-reset">
                        <?= e($app['client_name']) ?>
                    </a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-muted small">
                    <?php if ($app['server_label']): ?>
                    <a href="<?= url('/servers/' . $app['server_id']) ?>" class="text-reset">
                        <?= e($app['server_label']) ?>
                    </a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if ($app['cred_count'] > 0): ?>
                    <a href="<?= url('/applications/' . $app['id'] . '#tab-credentials') ?>"
                       class="badge bg-secondary-lt text-secondary text-decoration-none">
                        <?= (int) $app['cred_count'] ?>
                    </a>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="<?= url('/applications/' . $app['id']) ?>"
                           class="btn btn-ghost-secondary" title="View">
                            <i class="ti ti-eye"></i>
                        </a>
                        <a href="<?= url('/applications/' . $app['id'] . '/edit') ?>"
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

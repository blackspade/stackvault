<?php
/**
 * Vars: $databases[], $clients[], $types[], $search, $filterType, $filterClient
 */

ob_start(); ?>
<a href="<?= url('/databases/create') ?>" class="btn btn-primary d-none d-sm-inline-flex">
    <i class="ti ti-plus me-1"></i>Add Database
</a>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Filter bar ──────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= url('/databases') ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control"
                       placeholder="Search name, host, username, client…"
                       value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($types as $typeKey => $typeLabel): ?>
                    <option value="<?= e($typeKey) ?>" <?= $filterType === $typeKey ? 'selected' : '' ?>>
                        <?= e($typeLabel) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="client_id" class="form-select">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterClient === (int)$c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="ti ti-search me-1"></i>Filter
                </button>
                <?php if ($search || $filterType || $filterClient): ?>
                <a href="<?= url('/databases') ?>" class="btn btn-outline-secondary" title="Clear">
                    <i class="ti ti-x"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ── Table ──────────────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?= count($databases) ?> database<?= count($databases) !== 1 ? 's' : '' ?>
            <?php if ($search || $filterType || $filterClient): ?>
            <span class="badge bg-blue-lt text-blue ms-2">filtered</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($databases)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-database-off fs-1 d-block mb-2 opacity-50"></i>
        <?php if ($search || $filterType || $filterClient): ?>
            No databases match your filters.
        <?php else: ?>
            No databases yet. <a href="<?= url('/databases/create') ?>">Add your first database.</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th style="width:110px">Type</th>
                    <th>Database</th>
                    <th>Host</th>
                    <th>Username</th>
                    <th>Client</th>
                    <th>Server</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($databases as $db): ?>
            <?php
                $badgeClass = \App\Models\DatabaseModel::typeBadgeClass($db['db_type']);
                $typeLabel  = $types[$db['db_type']] ?? $db['db_type'];
            ?>
            <tr>
                <td>
                    <span class="badge <?= $badgeClass ?>"><?= e($typeLabel) ?></span>
                </td>
                <td>
                    <a href="<?= url('/databases/' . $db['id']) ?>"
                       class="fw-medium text-reset text-decoration-none font-monospace">
                        <?= e($db['db_name']) ?>
                    </a>
                    <?php if ($db['app_name']): ?>
                    <br><span class="text-muted small">
                        <i class="ti ti-app-window me-1"></i><?= e($db['app_name']) ?>
                    </span>
                    <?php endif; ?>
                </td>
                <td class="text-muted small font-monospace">
                    <?= e($db['host']) ?><?= $db['port'] ? ':' . (int)$db['port'] : '' ?>
                </td>
                <td class="text-muted small font-monospace">
                    <?= $db['username'] ? e($db['username']) : '—' ?>
                </td>
                <td class="text-muted small">
                    <?php if ($db['client_name']): ?>
                    <a href="<?= url('/clients/' . $db['client_id']) ?>" class="text-reset">
                        <?= e($db['client_name']) ?>
                    </a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-muted small">
                    <?php if ($db['server_label']): ?>
                    <a href="<?= url('/servers/' . $db['server_id']) ?>" class="text-reset">
                        <?= e($db['server_label']) ?>
                    </a>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="<?= url('/databases/' . $db['id']) ?>"
                           class="btn btn-ghost-secondary" title="View">
                            <i class="ti ti-eye"></i>
                        </a>
                        <a href="<?= url('/databases/' . $db['id'] . '/edit') ?>"
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

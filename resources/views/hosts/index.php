<?php
/**
 * Vars: $machines[], $clients[], $search, $filterClient, $filterOs
 */

$hasFilter = $search !== '' || $filterClient > 0 || $filterOs !== '';
?>

<?php
ob_start(); ?>
<a href="<?= url('/hosts/create') ?>" class="btn btn-primary">
    <i class="ti ti-plus me-1"></i>New Host File
</a>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Filter bar ──────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= url('/hosts') ?>" class="row g-2 align-items-end">

            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search name, description, client…"
                       value="<?= e($search) ?>">
            </div>

            <div class="col-md-3">
                <select name="os" class="form-select form-select-sm">
                    <option value="">All OS</option>
                    <?php foreach (\App\Models\HostMachineModel::OS_TYPES as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $filterOs === $key ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <select name="client_id" class="form-select form-select-sm">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= (int) $c['id'] ?>"
                        <?= $filterClient === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter">
                    <i class="ti ti-search"></i>
                </button>
                <?php if ($hasFilter): ?>
                <a href="<?= url('/hosts') ?>" class="btn btn-sm btn-outline-secondary" title="Clear">
                    <i class="ti ti-x"></i>
                </a>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<!-- ── Machine list ────────────────────────────────────────────────────────── -->
<?php if (empty($machines)): ?>
<div class="card">
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-file-text fs-1 d-block mb-2 opacity-50"></i>
        <?= $hasFilter ? 'No host files match your filters.' : 'No host files saved yet.' ?>
        <?php if (!$hasFilter): ?>
        <div class="mt-3">
            <a href="<?= url('/hosts/create') ?>" class="btn btn-primary btn-sm">
                <i class="ti ti-plus me-1"></i>Add First Machine
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Machine</th>
                    <th style="width:110px">OS</th>
                    <th style="width:160px">Client</th>
                    <th style="width:80px" class="text-center">Has File</th>
                    <th style="width:130px">Last Updated</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($machines as $m): ?>
            <?php $badgeClass = \App\Models\HostMachineModel::osBadgeClass($m['os']); ?>
            <tr>
                <td>
                    <a href="<?= url('/hosts/' . $m['id']) ?>" class="fw-medium">
                        <?= e($m['name']) ?>
                    </a>
                    <?php if ($m['description']): ?>
                    <div class="text-muted small text-truncate" style="max-width:280px">
                        <?= e($m['description']) ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="badge <?= $badgeClass ?>">
                        <?= e(\App\Models\HostMachineModel::OS_TYPES[$m['os']] ?? ucfirst($m['os'])) ?>
                    </span>
                </td>
                <td class="text-muted small">
                    <?php if ($m['client_name']): ?>
                    <a href="<?= url('/clients/' . $m['client_id']) ?>" class="text-reset">
                        <?= e($m['client_name']) ?>
                    </a>
                    <?php else: ?>
                    <span class="opacity-50">—</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <?php if (!empty($m['hosts_file'])): ?>
                    <i class="ti ti-circle-check text-success" title="Hosts file stored"></i>
                    <?php else: ?>
                    <i class="ti ti-circle-dashed text-muted" title="No content yet"></i>
                    <?php endif; ?>
                </td>
                <td class="text-muted small">
                    <?= date('d M Y', strtotime($m['updated_at'])) ?>
                </td>
                <td class="text-end">
                    <a href="<?= url('/hosts/' . $m['id']) ?>"
                       class="btn btn-sm btn-ghost-secondary" title="View">
                        <i class="ti ti-eye"></i>
                    </a>
                    <a href="<?= url('/hosts/' . $m['id'] . '/edit') ?>"
                       class="btn btn-sm btn-ghost-secondary" title="Edit">
                        <i class="ti ti-pencil"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

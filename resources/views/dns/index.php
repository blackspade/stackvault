<?php
/**
 * Vars: $records[], $domains[], $types[], $search, $filterType, $filterDomain
 */

ob_start(); ?>
<a href="<?= url('/dns/apply-template') ?>" class="btn btn-primary d-none d-sm-inline-flex">
    <i class="ti ti-wand me-1"></i>Add Records from Template
</a>
<a href="<?= url('/dns/templates') ?>" class="btn btn-outline-secondary d-none d-sm-inline-flex">
    <i class="ti ti-template me-1"></i>Manage Templates
</a>
<a href="<?= url('/dns/create') ?>" class="btn btn-outline-secondary d-none d-sm-inline-flex">
    <i class="ti ti-plus me-1"></i>Add DNS Record
</a>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Filter bar ──────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= url('/dns') ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control"
                       placeholder="Search name, value, domain…"
                       value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($types as $typeKey => $_): ?>
                    <option value="<?= $typeKey ?>" <?= $filterType === $typeKey ? 'selected' : '' ?>>
                        <?= $typeKey ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="domain_id" class="form-select">
                    <option value="">All Domains</option>
                    <?php foreach ($domains as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $filterDomain === (int)$d['id'] ? 'selected' : '' ?>>
                        <?= e($d['root_domain']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="ti ti-search me-1"></i>Filter
                </button>
                <?php if ($search || $filterType || $filterDomain): ?>
                <a href="<?= url('/dns') ?>" class="btn btn-outline-secondary" title="Clear">
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
            <?= count($records) ?> record<?= count($records) !== 1 ? 's' : '' ?>
            <?php if ($search || $filterType || $filterDomain): ?>
            <span class="badge bg-blue-lt text-blue ms-2">filtered</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($records)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-sitemap fs-1 d-block mb-2 opacity-50"></i>
        <?php if ($search || $filterType || $filterDomain): ?>
            No DNS records match your filters.
        <?php else: ?>
            No DNS records yet. <a href="<?= url('/dns/create') ?>">Add your first record.</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover font-monospace">
            <thead>
                <tr>
                    <th style="width:80px">Type</th>
                    <th>Domain</th>
                    <th>Name</th>
                    <th>Value</th>
                    <th style="width:70px" class="text-end">TTL</th>
                    <th style="width:60px" class="text-end">Prio</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($records as $r): ?>
            <?php $badge = \App\Models\DnsRecordModel::typeBadgeClass($r['record_type']); ?>
            <tr>
                <td>
                    <span class="badge <?= $badge ?>"><?= e($r['record_type']) ?></span>
                </td>
                <td class="font-monospace small">
                    <a href="<?= url('/domains/' . $r['domain_id']) ?>" class="text-reset">
                        <?= e($r['root_domain']) ?>
                    </a>
                </td>
                <td>
                    <a href="<?= url('/dns/' . $r['id']) ?>" class="fw-medium text-reset text-decoration-none">
                        <?= e($r['name']) ?>
                    </a>
                </td>
                <td class="text-muted small text-truncate" style="max-width:280px;"
                    title="<?= e($r['value']) ?>">
                    <?= e($r['value']) ?>
                </td>
                <td class="text-muted text-end"><?= (int) $r['ttl'] ?></td>
                <td class="text-muted text-end"><?= $r['priority'] !== null ? (int) $r['priority'] : '—' ?></td>
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

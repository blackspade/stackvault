<?php
/**
 * Vars: $logs[], $filters[], $page, $totalPages, $total, $perPage,
 *       $entityTypes[], $actionGroups[], $entityLabels[], $urlPrefixes[]
 */

// Build a query-string helper that preserves current filters + overrides
$qs = function(array $overrides = []) use ($filters, $page): string {
    $merged = array_merge($filters, ['page' => $page], $overrides);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== null && $v !== 0);
    return $merged ? '?' . http_build_query($merged) : '';
};

$hasFilter = array_filter($filters, fn($v) => $v !== '');
?>

<!-- ── Filter bar ──────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= url('/logs') ?>" class="row g-2 align-items-end">

            <div class="col-md-3">
                <input type="text" name="search" class="form-control form-control-sm"
                       placeholder="Search description, action, IP…"
                       value="<?= e($filters['search']) ?>">
            </div>

            <div class="col-md-2">
                <select name="entity_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <?php foreach ($entityTypes as $et): ?>
                    <option value="<?= e($et) ?>"
                        <?= $filters['entity_type'] === $et ? 'selected' : '' ?>>
                        <?= e($entityLabels[$et] ?? ucfirst(str_replace('_', ' ', $et))) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="action_group" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <?php foreach ($actionGroups as $key => $label): ?>
                    <option value="<?= $key ?>"
                        <?= $filters['action_group'] === $key ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control form-control-sm"
                       value="<?= e($filters['date_from']) ?>" placeholder="From date">
            </div>

            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control form-control-sm"
                       value="<?= e($filters['date_to']) ?>" placeholder="To date">
            </div>

            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-fill" title="Filter">
                    <i class="ti ti-search"></i>
                </button>
                <?php if ($hasFilter): ?>
                <a href="<?= url('/logs') ?>" class="btn btn-sm btn-outline-secondary" title="Clear">
                    <i class="ti ti-x"></i>
                </a>
                <?php endif; ?>
            </div>

        </form>
    </div>
</div>

<!-- ── Results card ───────────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <div class="card-title">
            <?= number_format($total) ?> event<?= $total !== 1 ? 's' : '' ?>
            <?php if ($hasFilter): ?>
            <span class="badge bg-blue-lt text-blue ms-2">filtered</span>
            <?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
        <small class="text-muted">
            Page <?= $page ?> of <?= $totalPages ?>
        </small>
        <?php endif; ?>
    </div>

    <?php if (empty($logs)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-activity fs-1 d-block mb-2 opacity-50"></i>
        <?= $hasFilter ? 'No events match your filters.' : 'No activity logged yet.' ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-sm">
            <thead>
                <tr>
                    <th style="width:155px">When</th>
                    <th style="width:170px">Action</th>
                    <th style="width:130px">Entity</th>
                    <th>Description</th>
                    <th style="width:100px">User</th>
                    <th style="width:120px">IP</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
            <?php
                $badgeClass  = \App\Models\ActivityLogModel::actionBadgeClass($log['action']);
                $entityLabel = $entityLabels[$log['entity_type']] ?? ucfirst(str_replace('_', ' ', (string)$log['entity_type']));
                $entityUrl   = isset($urlPrefixes[$log['entity_type']])
                               ? url($urlPrefixes[$log['entity_type']] . '/' . $log['entity_id'])
                               : null;
            ?>
            <tr>
                <!-- Timestamp -->
                <td class="text-muted small" style="white-space:nowrap"
                    title="<?= e($log['created_at']) ?>">
                    <?= time_ago($log['created_at']) ?>
                    <div style="font-size:.7rem;opacity:.6"><?= date('d M y H:i', strtotime($log['created_at'])) ?></div>
                </td>

                <!-- Action badge -->
                <td>
                    <span class="badge <?= $badgeClass ?>" style="font-size:.7rem;white-space:normal;text-align:left">
                        <?= e(str_replace('_', ' ', $log['action'])) ?>
                    </span>
                </td>

                <!-- Entity -->
                <td class="small">
                    <?php if ($log['entity_type']): ?>
                    <span class="text-muted"><?= e($entityLabel) ?></span>
                    <?php if ($log['entity_id'] && $entityUrl): ?>
                    <a href="<?= $entityUrl ?>" class="ms-1 text-reset" title="View <?= e($entityLabel) ?> #<?= (int)$log['entity_id'] ?>">
                        <i class="ti ti-external-link" style="font-size:.8rem"></i>
                    </a>
                    <?php elseif ($log['entity_id']): ?>
                    <span class="text-muted ms-1">#<?= (int)$log['entity_id'] ?></span>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>

                <!-- Description -->
                <td class="text-muted small">
                    <?= $log['description'] ? e($log['description']) : '<span class="opacity-50">—</span>' ?>
                </td>

                <!-- User -->
                <td class="text-muted small font-monospace">
                    <?= $log['username'] ? e($log['username']) : '<span class="opacity-50">system</span>' ?>
                </td>

                <!-- IP -->
                <td class="text-muted small font-monospace" style="white-space:nowrap">
                    <?php if ($log['ip_address']): ?>
                    <a href="<?= url('/logs') . $qs(['search' => $log['ip_address'], 'page' => 1]) ?>"
                       class="text-reset text-decoration-none"
                       title="Filter by this IP">
                        <?= e($log['ip_address']) ?>
                    </a>
                    <?php else: ?>
                    <span class="opacity-50">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <!-- ── Pagination ──────────────────────────────────────────────────────── -->
    <div class="card-footer d-flex align-items-center justify-content-between">
        <p class="m-0 text-muted small">
            Showing <?= number_format(($page - 1) * $perPage + 1) ?>–<?= number_format(min($page * $perPage, $total)) ?>
            of <?= number_format($total) ?>
        </p>
        <ul class="pagination pagination-sm m-0">
            <!-- Previous -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= url('/logs') . $qs(['page' => $page - 1]) ?>">
                    <i class="ti ti-chevron-left"></i>
                </a>
            </li>

            <?php
            // Show at most 7 page numbers around the current page
            $window = 3;
            $start  = max(1, $page - $window);
            $end    = min($totalPages, $page + $window);
            if ($start > 1): ?>
            <li class="page-item">
                <a class="page-link" href="<?= url('/logs') . $qs(['page' => 1]) ?>">1</a>
            </li>
            <?php if ($start > 2): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; endif; ?>

            <?php for ($p = $start; $p <= $end; $p++): ?>
            <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= url('/logs') . $qs(['page' => $p]) ?>"><?= $p ?></a>
            </li>
            <?php endfor; ?>

            <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?>
            <li class="page-item disabled"><span class="page-link">…</span></li>
            <?php endif; ?>
            <li class="page-item">
                <a class="page-link" href="<?= url('/logs') . $qs(['page' => $totalPages]) ?>"><?= $totalPages ?></a>
            </li>
            <?php endif; ?>

            <!-- Next -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= url('/logs') . $qs(['page' => $page + 1]) ?>">
                    <i class="ti ti-chevron-right"></i>
                </a>
            </li>
        </ul>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

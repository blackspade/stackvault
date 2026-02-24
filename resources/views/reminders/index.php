<?php
/**
 * Vars: $reminders[], $overdueCount, $filters[], $types[], $typeIcons[], $typeColors[], $clients[]
 */

$statusTabs = [
    ''        => 'All',
    'pending' => 'Pending',
    'overdue' => 'Overdue',
    'done'    => 'Done',
];

$urgencyClass = function(int $days, bool $isDone): string {
    if ($isDone) return 'text-muted';
    if ($days < 0)  return 'text-danger fw-bold';
    if ($days === 0) return 'text-danger fw-bold';
    if ($days <= 7)  return 'text-orange fw-semibold';
    if ($days <= 14) return 'text-warning';
    return 'text-muted';
};

$dueLabel = function(int $days, bool $isDone): string {
    if ($isDone) return 'Done';
    if ($days < 0)  return abs($days) . 'd overdue';
    if ($days === 0) return 'Today';
    if ($days === 1) return 'Tomorrow';
    return 'In ' . $days . 'd';
};
?>

<!-- ── Status filter tabs ──────────────────────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <ul class="nav nav-pills">
        <?php foreach ($statusTabs as $key => $label): ?>
        <li class="nav-item">
            <a href="<?= url('/reminders') . ($key !== '' ? '?status=' . $key : '') ?>"
               class="nav-link <?= $filters['status'] === $key ? 'active' : '' ?>">
                <?= e($label) ?>
                <?php if ($key === 'overdue' && $overdueCount > 0): ?>
                <span class="badge bg-danger ms-1"><?= $overdueCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <!-- Type filter -->
    <form method="get" action="<?= url('/reminders') ?>" class="d-flex gap-2 align-items-center">
        <?php if ($filters['status'] !== ''): ?>
        <input type="hidden" name="status" value="<?= e($filters['status']) ?>">
        <?php endif; ?>
        <select name="type" class="form-select form-select-sm" style="width:auto"
                onchange="this.form.submit()">
            <option value="">All Types</option>
            <?php foreach ($types as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $filters['type'] === $k ? 'selected' : '' ?>>
                <?= e($label) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <?php if ($filters['type'] !== ''): ?>
        <a href="<?= url('/reminders') . ($filters['status'] !== '' ? '?status=' . e($filters['status']) : '') ?>"
           class="btn btn-sm btn-outline-secondary" title="Clear type filter">
            <i class="ti ti-x"></i>
        </a>
        <?php endif; ?>
    </form>
</div>

<!-- ── Reminders table ──────────────────────────────────────────────────────── -->
<div class="card">
    <?php if (empty($reminders)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-bell-off fs-1 d-block mb-2 opacity-40"></i>
        <?php if ($filters['status'] !== '' || $filters['type'] !== ''): ?>
            No reminders match the current filters.
        <?php else: ?>
            No reminders yet. <a href="<?= url('/reminders/create') ?>">Add your first reminder →</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-sm">
            <thead>
                <tr>
                    <th style="width:110px">Due</th>
                    <th>Title</th>
                    <th style="width:160px">Type</th>
                    <th style="width:150px">Client</th>
                    <th style="width:90px">Status</th>
                    <th style="width:1%"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reminders as $rem): ?>
            <?php
                $isDone    = (bool) $rem['is_done'];
                $days      = (int) $rem['days_until'];
                $typeKey   = $rem['type'] ?? 'custom';
                $typeColor = $typeColors[$typeKey] ?? 'secondary';
                $typeIcon  = $typeIcons[$typeKey]  ?? 'ti-bell';
            ?>
            <tr class="<?= $isDone ? 'opacity-60' : '' ?>">

                <!-- Due date -->
                <td>
                    <span class="fw-medium <?= $urgencyClass($days, $isDone) ?>">
                        <?= $dueLabel($days, $isDone) ?>
                    </span>
                    <div class="text-muted small"><?= e($rem['due_date']) ?></div>
                </td>

                <!-- Title + notes -->
                <td>
                    <div class="fw-medium <?= $isDone ? 'text-decoration-line-through text-muted' : '' ?>">
                        <?= e($rem['title']) ?>
                    </div>
                    <?php if ($rem['notes']): ?>
                    <div class="text-muted small text-truncate" style="max-width:320px"
                         title="<?= e($rem['notes']) ?>">
                        <?= e($rem['notes']) ?>
                    </div>
                    <?php endif; ?>
                </td>

                <!-- Type badge -->
                <td>
                    <span class="badge bg-<?= $typeColor ?>-lt text-<?= $typeColor ?>">
                        <i class="ti <?= $typeIcon ?> me-1"></i><?= e($types[$typeKey] ?? $typeKey) ?>
                    </span>
                </td>

                <!-- Client -->
                <td class="text-muted small">
                    <?= $rem['client_name'] ? e($rem['client_name']) : '—' ?>
                </td>

                <!-- Status -->
                <td>
                    <?php if ($isDone): ?>
                    <span class="badge bg-success-lt text-success">
                        <i class="ti ti-circle-check me-1"></i>Done
                    </span>
                    <?php elseif ($days < 0): ?>
                    <span class="badge bg-danger-lt text-danger">
                        <i class="ti ti-alert-circle me-1"></i>Overdue
                    </span>
                    <?php elseif ($days === 0): ?>
                    <span class="badge bg-orange-lt text-orange">
                        <i class="ti ti-clock me-1"></i>Today
                    </span>
                    <?php elseif ($days <= 7): ?>
                    <span class="badge bg-warning-lt text-warning">
                        <i class="ti ti-clock me-1"></i>Soon
                    </span>
                    <?php else: ?>
                    <span class="badge bg-secondary-lt text-muted">
                        <i class="ti ti-hourglass me-1"></i>Upcoming
                    </span>
                    <?php endif; ?>
                </td>

                <!-- Actions -->
                <td>
                    <div class="d-flex gap-1 justify-content-end">
                        <!-- Mark done / undone -->
                        <?php if ($isDone): ?>
                        <form method="post" action="<?= url('/reminders/' . $rem['id'] . '/undone') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-ghost-secondary"
                                    title="Reopen">
                                <i class="ti ti-rotate-clockwise"></i>
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="post" action="<?= url('/reminders/' . $rem['id'] . '/done') ?>">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-ghost-success"
                                    title="Mark as done">
                                <i class="ti ti-circle-check"></i>
                            </button>
                        </form>
                        <?php endif; ?>

                        <!-- Edit -->
                        <a href="<?= url('/reminders/' . $rem['id'] . '/edit') ?>"
                           class="btn btn-sm btn-ghost-secondary" title="Edit">
                            <i class="ti ti-pencil"></i>
                        </a>

                        <!-- Delete -->
                        <form method="post" action="<?= url('/reminders/' . $rem['id'] . '/delete') ?>"
                              onsubmit="return confirm('Delete this reminder?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-sm btn-ghost-danger" title="Delete">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

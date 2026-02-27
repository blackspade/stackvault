<?php
/**
 * Vars: $licenses[], $stats[], $filters[], $allPlans[], $clients[],
 *       $sources[], $sourceColors[], $intervals[]
 */
?>

<!-- ── Summary stat cards ────────────────────────────────────────────────────── -->
<div class="row row-deck row-cards mb-4">

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar avatar-sm bg-blue-lt text-blue">
                            <i class="ti ti-brand-windows fs-3"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium"><?= (int) $stats['active_count'] ?></div>
                        <div class="text-muted small">Active Licenses</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar avatar-sm <?= $stats['unpaid_count'] > 0 ? 'bg-danger-lt text-danger' : 'bg-green-lt text-green' ?>">
                            <i class="ti ti-receipt fs-3"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium"><?= (int) $stats['unpaid_count'] ?></div>
                        <div class="text-muted small">Unpaid Invoices</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar avatar-sm bg-teal-lt text-teal">
                            <i class="ti ti-currency-dollar fs-3"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium">$<?= number_format($stats['monthly_recurring'], 2) ?></div>
                        <div class="text-muted small">Monthly Recurring</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-lg-3">
        <div class="card card-sm">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <span class="avatar avatar-sm <?= $stats['expiring_soon'] > 0 ? 'bg-warning-lt text-warning' : 'bg-secondary-lt text-muted' ?>">
                            <i class="ti ti-calendar-exclamation fs-3"></i>
                        </span>
                    </div>
                    <div class="col">
                        <div class="font-weight-medium"><?= (int) $stats['expiring_soon'] ?></div>
                        <div class="text-muted small">Expiring (60 days)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ── Filters ───────────────────────────────────────────────────────────────── -->
<form method="get" action="<?= url('/m365') ?>" class="mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-center">

        <!-- Client filter -->
        <select name="client_id" class="form-select form-select-sm" style="width:auto"
                onchange="this.form.submit()">
            <option value="">All Clients</option>
            <?php foreach ($clients as $c): ?>
            <option value="<?= (int) $c['id'] ?>"
                <?= (string)(int)$filters['clientId'] === (string)$c['id'] ? 'selected' : '' ?>>
                <?= e($c['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <!-- Plan filter -->
        <select name="plan" class="form-select form-select-sm" style="width:auto"
                onchange="this.form.submit()">
            <option value="">All Plans</option>
            <?php foreach ($allPlans as $p): ?>
            <option value="<?= e($p) ?>" <?= $filters['plan'] === $p ? 'selected' : '' ?>>
                <?= e($p) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <!-- Source filter -->
        <select name="source" class="form-select form-select-sm" style="width:auto"
                onchange="this.form.submit()">
            <option value="">All Sources</option>
            <?php foreach ($sources as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $filters['source'] === $k ? 'selected' : '' ?>>
                <?= e($label) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <!-- Status filter -->
        <select name="status" class="form-select form-select-sm" style="width:auto"
                onchange="this.form.submit()">
            <option value="active"   <?= $filters['status'] === 'active'   ? 'selected' : '' ?>>Active</option>
            <option value="inactive" <?= $filters['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            <option value="all"      <?= $filters['status'] === 'all'      ? 'selected' : '' ?>>All</option>
        </select>

        <!-- Clear filters -->
        <?php if ($filters['clientId'] || $filters['plan'] || $filters['source'] || $filters['status'] !== 'active'): ?>
        <a href="<?= url('/m365') ?>" class="btn btn-sm btn-outline-secondary">
            <i class="ti ti-x me-1"></i>Clear
        </a>
        <?php endif; ?>

    </div>
</form>

<!-- ── Licenses table ────────────────────────────────────────────────────────── -->
<div class="card">
    <?php if (empty($licenses)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-brand-windows fs-1 d-block mb-2 opacity-40"></i>
        <?php if ($filters['clientId'] || $filters['plan'] || $filters['source']): ?>
            No licenses match the current filters.
        <?php else: ?>
            No M365 licenses yet. <a href="<?= url('/m365/create') ?>">Add your first license →</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-sm">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Plan</th>
                    <th style="width:130px">Source</th>
                    <th style="width:60px" class="text-center">Seats</th>
                    <th style="width:110px" class="text-end">Amount</th>
                    <th style="width:130px">Next Billing</th>
                    <th style="width:110px">Expiry</th>
                    <th style="width:100px">Unpaid</th>
                    <th style="width:1%"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($licenses as $lic): ?>
            <?php
                $isActive    = (bool) $lic['is_active'];
                $unpaidCount = (int)  $lic['unpaid_count'];
                $unpaidTotal = (float)$lic['unpaid_total'];
                $srcColor    = $sourceColors[$lic['license_source']] ?? 'secondary';
                $srcLabel    = $sources[$lic['license_source']]     ?? $lic['license_source'];

                // Expiry urgency
                $expiryDays = $lic['expiry_date'] ? (int) $lic['expiry_days'] : null;
                if ($expiryDays === null) {
                    $expiryClass = 'text-muted';
                    $expiryLabel = '—';
                } elseif ($expiryDays < 0) {
                    $expiryClass = 'text-danger fw-bold';
                    $expiryLabel = 'Expired';
                } elseif ($expiryDays <= 30) {
                    $expiryClass = 'text-danger fw-semibold';
                    $expiryLabel = 'In ' . $expiryDays . 'd';
                } elseif ($expiryDays <= 60) {
                    $expiryClass = 'text-orange fw-semibold';
                    $expiryLabel = 'In ' . $expiryDays . 'd';
                } else {
                    $expiryClass = 'text-muted';
                    $expiryLabel = 'In ' . $expiryDays . 'd';
                }

                // Next billing urgency
                $nbd     = $lic['next_billing_date'];
                $nbdDiff = $nbd ? (int) (new \DateTime())->diff(new \DateTime($nbd))->format('%r%a') : null;
                if ($nbd === null) {
                    $nbdLabel = '—';
                    $nbdClass = 'text-muted';
                } elseif ($nbdDiff < 0) {
                    $nbdLabel = abs($nbdDiff) . 'd overdue';
                    $nbdClass = 'text-danger fw-bold';
                } elseif ($nbdDiff === 0) {
                    $nbdLabel = 'Today';
                    $nbdClass = 'text-danger fw-bold';
                } elseif ($nbdDiff <= 7) {
                    $nbdLabel = 'In ' . $nbdDiff . 'd';
                    $nbdClass = 'text-orange fw-semibold';
                } else {
                    $nbdLabel = 'In ' . $nbdDiff . 'd';
                    $nbdClass = 'text-muted';
                }

                $intervalLabel = $intervals[$lic['billing_interval']] ?? $lic['billing_interval'];
            ?>
            <tr class="<?= !$isActive ? 'opacity-50' : '' ?>">

                <!-- Client -->
                <td>
                    <a href="<?= url('/m365/' . $lic['id']) ?>" class="fw-medium text-decoration-none">
                        <?= e($lic['client_name']) ?>
                    </a>
                    <?php if (!$isActive): ?>
                    <span class="badge bg-secondary-lt text-muted ms-1">Inactive</span>
                    <?php endif; ?>
                </td>

                <!-- Plan -->
                <td>
                    <span class="badge bg-blue-lt text-blue">
                        <i class="ti ti-brand-windows me-1"></i><?= e($lic['plan']) ?>
                    </span>
                </td>

                <!-- Source -->
                <td>
                    <span class="badge bg-<?= $srcColor ?>-lt text-<?= $srcColor ?>">
                        <?= e($srcLabel) ?>
                    </span>
                </td>

                <!-- Seats -->
                <td class="text-center text-muted"><?= (int) $lic['seats'] ?></td>

                <!-- Amount -->
                <td class="text-end fw-medium">
                    $<?= number_format((float) $lic['amount'], 2) ?>
                    <div class="text-muted small"><?= e($intervalLabel) ?></div>
                </td>

                <!-- Next billing -->
                <td>
                    <?php if ($nbd): ?>
                    <span class="<?= $nbdClass ?>"><?= $nbdLabel ?></span>
                    <div class="text-muted small"><?= e($nbd) ?></div>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>

                <!-- Expiry -->
                <td>
                    <?php if ($lic['expiry_date']): ?>
                    <span class="<?= $expiryClass ?>"><?= $expiryLabel ?></span>
                    <div class="text-muted small"><?= e($lic['expiry_date']) ?></div>
                    <?php else: ?>
                    <span class="text-muted">—</span>
                    <?php endif; ?>
                </td>

                <!-- Unpaid -->
                <td>
                    <?php if ($unpaidCount > 0): ?>
                    <a href="<?= url('/m365/' . $lic['id']) ?>"
                       class="badge bg-danger text-white text-decoration-none">
                        <?= $unpaidCount ?> unpaid
                    </a>
                    <div class="text-muted small">$<?= number_format($unpaidTotal, 2) ?></div>
                    <?php else: ?>
                    <span class="badge bg-success-lt text-success">
                        <i class="ti ti-circle-check me-1"></i>Clear
                    </span>
                    <?php endif; ?>
                </td>

                <!-- Actions -->
                <td>
                    <div class="d-flex gap-1 justify-content-end">
                        <a href="<?= url('/m365/' . $lic['id']) ?>"
                           class="btn btn-sm btn-ghost-secondary" title="View detail">
                            <i class="ti ti-eye"></i>
                        </a>
                        <a href="<?= url('/m365/' . $lic['id'] . '/edit') ?>"
                           class="btn btn-sm btn-ghost-secondary" title="Edit">
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

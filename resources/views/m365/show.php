<?php
/**
 * Vars: $license[], $billingRecords[], $sources[], $sourceColors[], $sourceIcons[], $intervals[]
 */

$srcColor  = $sourceColors[$license['license_source']] ?? 'secondary';
$srcLabel  = $sources[$license['license_source']]      ?? $license['license_source'];
$srcIcon   = $sourceIcons[$license['license_source']]  ?? 'ti-user';

$intervalLabel = $intervals[$license['billing_interval']] ?? $license['billing_interval'];
if ($license['billing_interval'] === 'custom') {
    $intervalLabel .= ' (' . $license['billing_days'] . ' days)';
}

$expiryDays = $license['expiry_date'] ? (int) $license['expiry_days'] : null;
if ($expiryDays === null) {
    $expiryText  = '—';
    $expiryClass = 'text-muted';
} elseif ($expiryDays < 0) {
    $expiryText  = 'Expired ' . abs($expiryDays) . ' days ago';
    $expiryClass = 'text-danger fw-bold';
} elseif ($expiryDays <= 30) {
    $expiryText  = $license['expiry_date'] . ' (' . $expiryDays . 'd remaining)';
    $expiryClass = 'text-danger fw-semibold';
} elseif ($expiryDays <= 60) {
    $expiryText  = $license['expiry_date'] . ' (' . $expiryDays . 'd remaining)';
    $expiryClass = 'text-orange fw-semibold';
} else {
    $expiryText  = $license['expiry_date'] . ' (' . $expiryDays . 'd remaining)';
    $expiryClass = 'text-muted';
}

$unpaidCount = (int)  $license['unpaid_count'];
$unpaidTotal = (float)$license['unpaid_total'];
?>

<div class="row row-cards g-3">

    <!-- ── License info card ─────────────────────────────────────────────────── -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-brand-windows me-2 text-blue"></i>License Details
                </h3>
                <div class="card-options d-flex gap-2">

                    <a href="<?= url('/m365/' . $license['id'] . '/edit') ?>"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="ti ti-pencil me-1"></i>Edit
                    </a>

                    <!-- Toggle active -->
                    <form method="post" action="<?= url('/m365/' . $license['id'] . '/toggle') ?>">
                        <?= csrf_field() ?>
                        <button type="submit"
                                class="btn btn-sm <?= $license['is_active'] ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                                onclick="return confirm('<?= $license['is_active'] ? 'Deactivate this license?' : 'Reactivate this license?' ?>')">
                            <?php if ($license['is_active']): ?>
                            <i class="ti ti-player-pause me-1"></i>Deactivate
                            <?php else: ?>
                            <i class="ti ti-player-play me-1"></i>Reactivate
                            <?php endif; ?>
                        </button>
                    </form>

                    <!-- Delete -->
                    <form method="post" action="<?= url('/m365/' . $license['id'] . '/delete') ?>"
                          onsubmit="return confirm('Delete this license and ALL billing records? This cannot be undone.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="ti ti-trash me-1"></i>Delete
                        </button>
                    </form>

                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Client</div>
                        <div class="fw-medium"><?= e($license['client_name']) ?></div>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Plan</div>
                        <span class="badge bg-blue-lt text-blue">
                            <i class="ti ti-brand-windows me-1"></i><?= e($license['plan']) ?>
                        </span>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">License Source</div>
                        <span class="badge bg-<?= $srcColor ?>-lt text-<?= $srcColor ?>">
                            <i class="ti <?= $srcIcon ?> me-1"></i><?= e($srcLabel) ?>
                        </span>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Status</div>
                        <?php if ($license['is_active']): ?>
                        <span class="badge bg-success-lt text-success">
                            <i class="ti ti-circle-check me-1"></i>Active
                        </span>
                        <?php else: ?>
                        <span class="badge bg-secondary-lt text-muted">
                            <i class="ti ti-circle-minus me-1"></i>Inactive
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Seats</div>
                        <div class="fw-medium"><?= (int) $license['seats'] ?></div>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Amount per Period</div>
                        <div class="fw-medium">$<?= number_format((float) $license['amount'], 2) ?></div>
                        <div class="text-muted small"><?= e($intervalLabel) ?></div>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">Next Billing Date</div>
                        <?php if ($license['next_billing_date']): ?>
                        <div class="fw-medium"><?= e($license['next_billing_date']) ?></div>
                        <div class="text-muted small">
                            Remind <?= (int) $license['remind_days'] ?> days before
                        </div>
                        <?php else: ?>
                        <div class="text-muted">Not scheduled</div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-3">
                        <div class="text-muted small mb-1">License Expiry</div>
                        <div class="<?= $expiryClass ?> fw-medium"><?= e($expiryText) ?></div>
                    </div>

                    <?php if ($license['notes']): ?>
                    <div class="col-12">
                        <div class="text-muted small mb-1">Notes</div>
                        <div class="text-break" style="white-space:pre-wrap"><?= e($license['notes']) ?></div>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <!-- ── Billing history card ──────────────────────────────────────────────── -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-receipt me-2 text-indigo"></i>Billing History
                </h3>
                <?php if ($unpaidCount > 0): ?>
                <div class="card-options">
                    <span class="badge bg-danger">
                        <?= $unpaidCount ?> unpaid — $<?= number_format($unpaidTotal, 2) ?> total
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <?php if (empty($billingRecords)): ?>
            <div class="card-body text-center text-muted py-4">
                <i class="ti ti-receipt-off fs-1 d-block mb-2 opacity-40"></i>
                No billing records yet. Records are auto-generated when the next billing date arrives.
                <?php if ($license['next_billing_date']): ?>
                <br>Next record will be created on
                <strong><?= e($license['next_billing_date']) ?></strong>.
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm">
                    <thead>
                        <tr>
                            <th>Period</th>
                            <th style="width:90px" class="text-end">Amount</th>
                            <th style="width:120px">Due Date</th>
                            <th style="width:130px">Status</th>
                            <th style="width:130px">Paid On</th>
                            <th style="width:1%"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($billingRecords as $rec): ?>
                    <?php
                        $isPaid  = (bool) $rec['is_paid'];
                        $isDismissed = (bool) $rec['is_acknowledged'];
                        $days    = (int)  $rec['days_until'];

                        if ($isPaid) {
                            $statusBadge = '<span class="badge bg-success-lt text-success"><i class="ti ti-circle-check me-1"></i>Paid</span>';
                            $rowClass    = 'opacity-60';
                        } elseif ($days < 0) {
                            $statusBadge = '<span class="badge bg-danger-lt text-danger"><i class="ti ti-alert-circle me-1"></i>Overdue ' . abs($days) . 'd</span>';
                            $rowClass    = 'table-danger';
                        } elseif ($days === 0) {
                            $statusBadge = '<span class="badge bg-orange-lt text-orange"><i class="ti ti-clock me-1"></i>Due Today</span>';
                            $rowClass    = '';
                        } elseif ($days <= 7) {
                            $statusBadge = '<span class="badge bg-warning-lt text-warning"><i class="ti ti-clock me-1"></i>Due in ' . $days . 'd</span>';
                            $rowClass    = '';
                        } else {
                            $statusBadge = '<span class="badge bg-secondary-lt text-muted"><i class="ti ti-hourglass me-1"></i>In ' . $days . 'd</span>';
                            $rowClass    = '';
                        }
                    ?>
                    <tr class="<?= $rowClass ?>">

                        <!-- Period -->
                        <td>
                            <div class="fw-medium"><?= e($rec['period_label']) ?></div>
                            <div class="text-muted small">
                                <?= e($rec['period_start']) ?> – <?= e($rec['period_end']) ?>
                            </div>
                            <?php if (!$isPaid && $isDismissed): ?>
                            <span class="badge bg-secondary-lt text-muted mt-1">
                                <i class="ti ti-eye-off me-1"></i>Dismissed
                            </span>
                            <?php endif; ?>
                        </td>

                        <!-- Amount -->
                        <td class="text-end fw-medium">
                            $<?= number_format((float) $rec['amount'], 2) ?>
                        </td>

                        <!-- Due Date -->
                        <td>
                            <div><?= e($rec['due_date']) ?></div>
                        </td>

                        <!-- Status -->
                        <td><?= $statusBadge ?></td>

                        <!-- Paid On -->
                        <td class="text-muted small">
                            <?= $rec['paid_at'] ? e(date('M j, Y', strtotime($rec['paid_at']))) : '—' ?>
                        </td>

                        <!-- Actions -->
                        <td>
                            <div class="d-flex gap-1 justify-content-end">
                                <?php if (!$isPaid): ?>

                                    <!-- Mark Paid -->
                                    <form method="post"
                                          action="<?= url('/m365/billing/' . $rec['id'] . '/pay') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                class="btn btn-sm btn-ghost-success"
                                                title="Mark as paid">
                                            <i class="ti ti-circle-check"></i>
                                        </button>
                                    </form>

                                    <?php if ($isDismissed): ?>
                                    <!-- Restore (un-dismiss) -->
                                    <form method="post"
                                          action="<?= url('/m365/billing/' . $rec['id'] . '/restore') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                class="btn btn-sm btn-ghost-secondary"
                                                title="Restore to dashboard">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <!-- Dismiss -->
                                    <form method="post"
                                          action="<?= url('/m365/billing/' . $rec['id'] . '/dismiss') ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit"
                                                class="btn btn-sm btn-ghost-secondary"
                                                title="Dismiss from dashboard">
                                            <i class="ti ti-eye-off"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>

                                <?php endif; ?>
                            </div>
                        </td>

                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </div>
    </div>

</div>

<?php
/**
 * Vars: $template[], $domain[], $records[]
 *
 * Each record has an added 'conflict' (bool) key.
 */

$conflicts   = array_filter($records, fn($r) => $r['conflict']);
$safeRecords = array_filter($records, fn($r) => !$r['conflict']);
?>

<!-- ── Summary banner ─────────────────────────────────────────────────────── -->
<div class="alert alert-info mb-4">
    <div class="d-flex gap-2 align-items-center">
        <i class="ti ti-info-circle fs-4 flex-shrink-0"></i>
        <div>
            Applying template <strong><?= e($template['name']) ?></strong>
            to <strong><?= e($domain['root_domain']) ?></strong>.
            <?php if (!empty($conflicts)): ?>
            <br>
            <span class="text-warning fw-medium">
                <i class="ti ti-alert-triangle me-1"></i>
                <?= count($conflicts) ?> conflicting record<?= count($conflicts) !== 1 ? 's' : '' ?>
                (same type + name already exists) — unchecked by default.
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<form method="post" action="<?= url('/dns/apply-template/confirm') ?>">
    <?= csrf_field() ?>

    <div class="card">
        <div class="card-header d-flex align-items-center gap-2">
            <h4 class="card-title mb-0">Records to Create</h4>
            <span class="badge bg-secondary-lt text-secondary">
                <?= count($records) ?> record<?= count($records) !== 1 ? 's' : '' ?> total
            </span>
            <div class="ms-auto d-flex gap-2">
                <button type="button" class="btn btn-sm btn-ghost-secondary" id="btn-select-all">
                    Select All
                </button>
                <button type="button" class="btn btn-sm btn-ghost-secondary" id="btn-select-safe">
                    Select Non-conflicting
                </button>
                <button type="button" class="btn btn-sm btn-ghost-secondary" id="btn-deselect-all">
                    Deselect All
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter card-table font-monospace mb-0">
                <thead>
                    <tr>
                        <th style="width:40px">
                            <input type="checkbox" class="form-check-input" id="chk-all" title="Toggle all">
                        </th>
                        <th style="width:80px">Type</th>
                        <th style="width:160px">Name</th>
                        <th>Value</th>
                        <th style="width:70px" class="text-end">TTL</th>
                        <th style="width:60px" class="text-end">Prio</th>
                        <th style="width:110px"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($records as $i => $rec):
                    $badge    = \App\Models\DnsRecordModel::typeBadgeClass($rec['record_type']);
                    $conflict = (bool) $rec['conflict'];
                    $checked  = !$conflict; // pre-check non-conflicting
                ?>
                <tr class="record-preview-row <?= $conflict ? 'table-warning' : '' ?>"
                    data-conflict="<?= $conflict ? '1' : '0' ?>">
                    <td>
                        <input type="checkbox" class="form-check-input row-chk"
                               name="records[]" value="<?= $i ?>"
                               <?= $checked ? 'checked' : '' ?>>
                    </td>
                    <td>
                        <span class="badge <?= $badge ?>"><?= e($rec['record_type']) ?></span>
                    </td>
                    <td><?= e($rec['name']) ?></td>
                    <td class="text-muted small text-truncate" style="max-width:300px"
                        title="<?= e($rec['value']) ?>">
                        <?= e($rec['value']) ?>
                    </td>
                    <td class="text-muted text-end"><?= (int) $rec['ttl'] ?></td>
                    <td class="text-muted text-end"><?= $rec['priority'] !== null ? (int) $rec['priority'] : '—' ?></td>
                    <td class="text-end">
                        <?php if ($conflict): ?>
                        <span class="badge bg-warning-lt text-warning">
                            <i class="ti ti-alert-triangle me-1"></i>Conflict
                        </span>
                        <?php else: ?>
                        <span class="badge bg-green-lt text-green">New</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2 align-items-center">
        <button type="submit" class="btn btn-primary" id="btn-confirm">
            <i class="ti ti-check me-1"></i>Apply Selected Records
        </button>
        <a href="<?= url('/dns/apply-template') ?>" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Back
        </a>
        <span class="text-muted small ms-2">
            <span id="selected-count"><?= count($safeRecords) ?></span> of <?= count($records) ?> selected
        </span>
    </div>
</form>

<script>
const allCheckboxes = () => document.querySelectorAll('.row-chk');

function updateCount() {
    const n = document.querySelectorAll('.row-chk:checked').length;
    document.getElementById('selected-count').textContent = n;
}

document.getElementById('btn-select-all').addEventListener('click', function () {
    allCheckboxes().forEach(c => c.checked = true);
    updateCount();
});

document.getElementById('btn-select-safe').addEventListener('click', function () {
    allCheckboxes().forEach(c => {
        c.checked = c.closest('tr').dataset.conflict !== '1';
    });
    updateCount();
});

document.getElementById('btn-deselect-all').addEventListener('click', function () {
    allCheckboxes().forEach(c => c.checked = false);
    updateCount();
});

document.getElementById('chk-all').addEventListener('change', function () {
    allCheckboxes().forEach(c => c.checked = this.checked);
    updateCount();
});

document.querySelectorAll('.row-chk').forEach(c => {
    c.addEventListener('change', updateCount);
});
</script>

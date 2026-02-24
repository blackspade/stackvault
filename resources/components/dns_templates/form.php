<?php
/**
 * DNS Template form component
 *
 * Required vars:
 *   $action       — form action URL
 *   $submitLabel  — submit button text
 *   $template[]   — field values (empty or partial for create)
 *   $records[]    — existing template records (empty for create)
 */

$currentName        = (string) ($template['name']        ?? '');
$currentDescription = (string) ($template['description'] ?? '');
$currentRecords     = $records ?? [];

$types         = \App\Models\DnsRecordModel::TYPES;
$priorityTypes = \App\Models\DnsRecordModel::PRIORITY_TYPES;
?>
<form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>

    <!-- ── Template info ──────────────────────────────────────────────────── -->
    <div class="row g-3 mb-4">

        <div class="col-md-6">
            <label class="form-label required">Template Name</label>
            <input type="text" name="name" class="form-control"
                   value="<?= e($currentName) ?>"
                   placeholder="e.g. cPanel Hosting" maxlength="120" required>
        </div>

        <div class="col-md-6">
            <label class="form-label">Description <span class="text-muted fw-normal">(optional)</span></label>
            <input type="text" name="description" class="form-control"
                   value="<?= e($currentDescription) ?>"
                   placeholder="Brief description of this template…" maxlength="255">
        </div>

    </div>

    <!-- ── Records ────────────────────────────────────────────────────────── -->
    <div class="card mb-4">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">DNS Records</h4>
            <div class="text-muted small">
                Use <code>{domain}</code>, <code>{ip}</code>, <code>{mail_server}</code>, <code>{spf}</code> as variables.
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter mb-0" id="records-table">
                <thead>
                    <tr>
                        <th style="width:90px">Type</th>
                        <th style="width:160px">Name</th>
                        <th>Value</th>
                        <th style="width:80px">TTL</th>
                        <th style="width:80px">Prio</th>
                        <th style="width:160px">Notes</th>
                        <th style="width:40px"></th>
                    </tr>
                </thead>
                <tbody id="records-body">
                <?php foreach ($currentRecords as $i => $rec):
                    $rtype = strtoupper((string) ($rec['record_type'] ?? 'A'));
                    $showPrio = in_array($rtype, $priorityTypes, true);
                ?>
                <tr class="record-row">
                    <td>
                        <select name="records[<?= $i ?>][record_type]" class="form-select form-select-sm record-type-sel">
                            <?php foreach ($types as $tkey => $_): ?>
                            <option value="<?= $tkey ?>" <?= $rtype === $tkey ? 'selected' : '' ?>><?= $tkey ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="records[<?= $i ?>][name]"
                               class="form-control form-control-sm font-monospace"
                               value="<?= e((string) ($rec['name'] ?? '')) ?>"
                               placeholder="@ or www" required>
                    </td>
                    <td>
                        <input type="text" name="records[<?= $i ?>][value]"
                               class="form-control form-control-sm font-monospace"
                               value="<?= e((string) ($rec['value'] ?? '')) ?>"
                               placeholder="value or {variable}" required>
                    </td>
                    <td>
                        <input type="number" name="records[<?= $i ?>][ttl]"
                               class="form-control form-control-sm"
                               value="<?= (int) ($rec['ttl'] ?? 3600) ?>"
                               min="0" max="2147483647">
                    </td>
                    <td>
                        <input type="number" name="records[<?= $i ?>][priority]"
                               class="form-control form-control-sm prio-field"
                               value="<?= $rec['priority'] !== null && $rec['priority'] !== '' ? (int) $rec['priority'] : '' ?>"
                               placeholder="—" min="0" max="65535"
                               <?= !$showPrio ? 'style="visibility:hidden"' : '' ?>>
                    </td>
                    <td>
                        <input type="text" name="records[<?= $i ?>][notes]"
                               class="form-control form-control-sm"
                               value="<?= e((string) ($rec['notes'] ?? '')) ?>"
                               placeholder="Optional">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-ghost-danger remove-row" title="Remove">
                            <i class="ti ti-x"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            <button type="button" id="add-row-btn" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-plus me-1"></i>Add Record Row
            </button>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i><?= e($submitLabel) ?>
        </button>
        <a href="<?= url('/dns/templates') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script>
const SV_PRIO_TYPES = <?= json_encode(\App\Models\DnsRecordModel::PRIORITY_TYPES) ?>;
const SV_TYPES      = <?= json_encode(array_keys(\App\Models\DnsRecordModel::TYPES)) ?>;

let svRowIndex = <?= max(count($currentRecords), 0) ?>;

function svBuildRow(idx) {
    const typeOptions = SV_TYPES.map(t =>
        `<option value="${t}">${t}</option>`
    ).join('');

    return `<tr class="record-row">
        <td>
            <select name="records[${idx}][record_type]" class="form-select form-select-sm record-type-sel">
                ${typeOptions}
            </select>
        </td>
        <td>
            <input type="text" name="records[${idx}][name]"
                   class="form-control form-control-sm font-monospace"
                   placeholder="@ or www" required>
        </td>
        <td>
            <input type="text" name="records[${idx}][value]"
                   class="form-control form-control-sm font-monospace"
                   placeholder="value or {variable}" required>
        </td>
        <td>
            <input type="number" name="records[${idx}][ttl]"
                   class="form-control form-control-sm"
                   value="3600" min="0" max="2147483647">
        </td>
        <td>
            <input type="number" name="records[${idx}][priority]"
                   class="form-control form-control-sm prio-field"
                   placeholder="—" min="0" max="65535"
                   style="visibility:hidden">
        </td>
        <td>
            <input type="text" name="records[${idx}][notes]"
                   class="form-control form-control-sm"
                   placeholder="Optional">
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-ghost-danger remove-row" title="Remove">
                <i class="ti ti-x"></i>
            </button>
        </td>
    </tr>`;
}

document.getElementById('add-row-btn').addEventListener('click', function () {
    const tbody = document.getElementById('records-body');
    tbody.insertAdjacentHTML('beforeend', svBuildRow(svRowIndex++));
    // Bind type change for new row
    const newRow = tbody.lastElementChild;
    bindTypeChange(newRow.querySelector('.record-type-sel'));
});

document.getElementById('records-body').addEventListener('click', function (e) {
    if (e.target.closest('.remove-row')) {
        e.target.closest('tr').remove();
    }
});

function bindTypeChange(sel) {
    sel.addEventListener('change', function () {
        const row   = this.closest('tr');
        const prio  = row.querySelector('.prio-field');
        const show  = SV_PRIO_TYPES.includes(this.value);
        prio.style.visibility = show ? 'visible' : 'hidden';
        if (!show) prio.value = '';
    });
}

// Bind existing rows
document.querySelectorAll('.record-type-sel').forEach(bindTypeChange);
</script>

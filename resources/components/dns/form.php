<?php
/**
 * DNS Record form component
 *
 * Required vars:
 *   $record[]       — field values (empty array for create)
 *   $types[]        — DnsRecordModel::TYPES
 *   $domains[]      — DomainModel::getForSelect()
 *   $action         — form action URL
 *   $submitLabel    — button text
 *   $return_to      — (optional) URL to return to after save/delete
 *   $presetDomainId — (optional) pre-selected domain_id (create only)
 */

$selectedDomain   = (int) ($record['domain_id']   ?? $presetDomainId ?? 0);
$selectedType     = (string) ($record['record_type'] ?? 'A');
$currentName      = (string) ($record['name']        ?? '');
$currentValue     = (string) ($record['value']       ?? '');
$currentTtl       = (int)    ($record['ttl']         ?? 3600);
$currentPriority  = $record['priority'] ?? '';
$currentNotes     = (string) ($record['notes']       ?? '');
$returnTo         = $return_to ?? '';

$priorityTypes = \App\Models\DnsRecordModel::PRIORITY_TYPES;
$valueHints    = \App\Models\DnsRecordModel::VALUE_HINTS;
?>
<form method="post" action="<?= $action ?>">
    <?= csrf_field() ?>
    <?php if ($returnTo): ?>
    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
    <?php endif; ?>

    <div class="row g-3">

        <!-- Domain -->
        <div class="col-md-6">
            <label class="form-label required">Domain</label>
            <select name="domain_id" class="form-select" required>
                <option value="">— Select domain —</option>
                <?php foreach ($domains as $d): ?>
                <option value="<?= $d['id'] ?>"
                    <?= $selectedDomain === (int) $d['id'] ? 'selected' : '' ?>>
                    <?= e($d['root_domain']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Record type -->
        <div class="col-md-3">
            <label class="form-label required">Type</label>
            <select name="record_type" id="inp-record-type" class="form-select"
                    onchange="svTypeChanged(this.value)" required>
                <?php foreach ($types as $typeKey => $_label): ?>
                <option value="<?= $typeKey ?>" <?= $selectedType === $typeKey ? 'selected' : '' ?>>
                    <?= $typeKey ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- TTL -->
        <div class="col-md-3">
            <label class="form-label required">TTL <span class="text-muted fw-normal">(seconds)</span></label>
            <input type="number" name="ttl" id="inp-ttl" class="form-control"
                   value="<?= $currentTtl ?>"
                   min="0" max="2147483647" required>
            <div class="form-text">Common: 300 · 3600 · 86400</div>
        </div>

        <!-- Name -->
        <div class="col-md-6">
            <label class="form-label required">Name</label>
            <input type="text" name="name" class="form-control font-monospace"
                   value="<?= e($currentName) ?>"
                   placeholder="@ or subdomain" required>
            <div class="form-text">Use <code>@</code> for the zone root, or enter a subdomain (e.g. <code>www</code>).</div>
        </div>

        <!-- Priority (shown for MX / SRV / CAA) -->
        <div class="col-md-3" id="priority-field"
             style="<?= in_array($selectedType, $priorityTypes, true) ? '' : 'display:none' ?>">
            <label class="form-label" id="priority-label">Priority</label>
            <input type="number" name="priority" id="inp-priority" class="form-control"
                   value="<?= e((string) $currentPriority) ?>"
                   min="0" max="65535">
            <div class="form-text" id="priority-hint">Lower = higher preference.</div>
        </div>

        <!-- Value -->
        <div class="col-12">
            <label class="form-label required">Value</label>
            <textarea name="value" id="inp-value" class="form-control font-monospace"
                      rows="3" required
                      placeholder="<?= e($valueHints[$selectedType] ?? '') ?>"><?= e($currentValue) ?></textarea>
            <div class="form-text" id="value-hint"><?= e($valueHints[$selectedType] ?? '') ?></div>
        </div>

        <!-- Notes -->
        <div class="col-12">
            <label class="form-label">Notes <span class="text-muted fw-normal">(optional)</span></label>
            <textarea name="notes" class="form-control" rows="2"
                      placeholder="Internal notes about this record…"><?= e($currentNotes) ?></textarea>
        </div>

    </div><!-- /.row -->

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i><?= e($submitLabel) ?>
        </button>
        <?php if ($returnTo): ?>
        <a href="<?= e($returnTo) ?>#tab-dns" class="btn btn-outline-secondary">Cancel</a>
        <?php else: ?>
        <a href="<?= url('/dns') ?>" class="btn btn-outline-secondary">Cancel</a>
        <?php endif; ?>
    </div>
</form>

<script>
const SV_VALUE_HINTS = <?= json_encode(array_map('htmlspecialchars_decode', \App\Models\DnsRecordModel::VALUE_HINTS)) ?>;
const SV_PRIORITY_TYPES = <?= json_encode(\App\Models\DnsRecordModel::PRIORITY_TYPES) ?>;

function svTypeChanged(type) {
    const priorityField = document.getElementById('priority-field');
    const valueHint     = document.getElementById('value-hint');
    const valueTa       = document.getElementById('inp-value');
    const showPriority  = SV_PRIORITY_TYPES.includes(type);

    priorityField.style.display = showPriority ? '' : 'none';
    document.getElementById('inp-priority').required = showPriority;

    const hint = SV_VALUE_HINTS[type] || '';
    valueHint.textContent    = hint;
    valueTa.placeholder      = hint;
}
</script>

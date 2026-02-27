<?php
/**
 * Shared M365 license form fields
 *
 * Vars: $clients[], $allPlans[], $sources[], $intervals[],
 *       $old[] (optional), $license[] (optional — edit mode)
 */

$old = $old ?? [];
$r   = $license ?? [];

$val = fn(string $k, mixed $default = '') => $old[$k] ?? $r[$k] ?? $default;

$selectedInterval = $val('billing_interval', 'monthly');
$selectedSource   = $val('license_source',   'vendor');

// Determine if the current plan is a custom (not in $allPlans)
$currentPlan  = $val('plan', '');
$isBuiltin    = in_array($currentPlan, $allPlans, true) || $currentPlan === '';
?>

<div class="row g-3">

    <!-- ── Client ──────────────────────────────────────────────────────────── -->
    <div class="col-md-6">
        <label class="form-label required" for="sv-client-input">Client</label>
        <div class="sv-select">
            <input type="hidden" id="client_id" name="client_id"
                   value="<?= (int) $val('client_id', 0) ?>">
            <input type="text" id="sv-client-input" class="form-control sv-select-input"
                   placeholder="Search client…" autocomplete="off">
            <div class="sv-select-dropdown">
                <div class="sv-select-option" data-id="">— No client —</div>
                <?php foreach ($clients as $c): ?>
                <div class="sv-select-option" data-id="<?= (int) $c['id'] ?>">
                    <?= e($c['name']) ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Plan ────────────────────────────────────────────────────────────── -->
    <div class="col-md-6">
        <label class="form-label required" for="plan">Plan</label>
        <input type="text" id="plan" name="plan"
               class="form-control"
               list="m365-plans-datalist"
               value="<?= e($currentPlan) ?>"
               placeholder="Select or type a plan name"
               maxlength="100"
               required>
        <datalist id="m365-plans-datalist">
            <?php foreach ($allPlans as $p): ?>
            <option value="<?= e($p) ?>">
            <?php endforeach; ?>
        </datalist>
        <div class="form-text">Choose a GoDaddy plan or type a custom plan name.</div>
    </div>

    <!-- ── License Source ──────────────────────────────────────────────────── -->
    <div class="col-12">
        <label class="form-label required">License Source</label>
        <div class="d-flex gap-4">
            <?php foreach ($sources as $key => $label): ?>
            <label class="form-check">
                <input type="radio" class="form-check-input"
                       name="license_source" value="<?= e($key) ?>"
                       <?= $selectedSource === $key ? 'checked' : '' ?>>
                <span class="form-check-label">
                    <?php if ($key === 'vendor'): ?>
                    <i class="ti ti-building-store me-1 text-green"></i>
                    <?php else: ?>
                    <i class="ti ti-user me-1 text-muted"></i>
                    <?php endif; ?>
                    <?= e($label) ?>
                </span>
            </label>
            <?php endforeach; ?>
        </div>
        <div class="form-text">
            "Vendor-provided" = we supply the license via our GoDaddy account.
            "Self-registered" = client manages their own account.
        </div>
    </div>

    <!-- ── Seats + Expiry ──────────────────────────────────────────────────── -->
    <div class="col-md-3">
        <label class="form-label required" for="seats">Seats</label>
        <input type="number" id="seats" name="seats"
               class="form-control"
               value="<?= (int) $val('seats', 1) ?>"
               min="1" max="9999" required>
        <div class="form-text">Number of user licences.</div>
    </div>

    <div class="col-md-3">
        <label class="form-label" for="expiry_date">
            License Expiry <span class="text-muted">(optional)</span>
        </label>
        <input type="date" id="expiry_date" name="expiry_date"
               class="form-control"
               value="<?= e($val('expiry_date')) ?>">
        <div class="form-text">Annual subscription end date.</div>
    </div>

    <!-- ── Billing Amount ──────────────────────────────────────────────────── -->
    <div class="col-md-3">
        <label class="form-label required" for="amount">Amount (USD)</label>
        <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" id="amount" name="amount"
                   class="form-control"
                   value="<?= e(number_format((float) $val('amount', '0'), 2, '.', '')) ?>"
                   step="0.01" min="0" required>
        </div>
        <div class="form-text">Amount charged per billing period.</div>
    </div>

    <!-- ── Billing Interval ────────────────────────────────────────────────── -->
    <div class="col-md-3">
        <label class="form-label required">Billing Interval</label>
        <div class="d-flex flex-column gap-1">
            <?php foreach ($intervals as $key => $label): ?>
            <label class="form-check">
                <input type="radio" class="form-check-input billing-interval-radio"
                       name="billing_interval" value="<?= e($key) ?>"
                       <?= $selectedInterval === $key ? 'checked' : '' ?>>
                <span class="form-check-label"><?= e($label) ?></span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Custom interval days (hidden unless Custom is selected) ─────────── -->
    <div class="col-md-3" id="billing-days-wrapper"
         style="<?= $selectedInterval !== 'custom' ? 'display:none' : '' ?>">
        <label class="form-label required" for="billing_days">Every N Days</label>
        <input type="number" id="billing_days" name="billing_days"
               class="form-control"
               value="<?= (int) $val('billing_days', 30) ?>"
               min="1" max="999">
        <div class="form-text">Billing cycle length in days.</div>
    </div>

    <!-- ── Next Billing Date ───────────────────────────────────────────────── -->
    <div class="col-md-3">
        <label class="form-label" for="next_billing_date">
            Next Billing Date <span class="text-muted">(optional)</span>
        </label>
        <input type="date" id="next_billing_date" name="next_billing_date"
               class="form-control"
               value="<?= e($val('next_billing_date')) ?>">
        <div class="form-text">
            When the next invoice should be sent. Leave blank if not yet scheduled.
        </div>
    </div>

    <!-- ── Remind N days before ────────────────────────────────────────────── -->
    <div class="col-md-3">
        <label class="form-label required" for="remind_days">Remind N Days Before</label>
        <div class="input-group">
            <input type="number" id="remind_days" name="remind_days"
                   class="form-control"
                   value="<?= (int) $val('remind_days', 5) ?>"
                   min="0" max="90" required>
            <span class="input-group-text">days</span>
        </div>
        <div class="form-text">Show on dashboard this many days before due.</div>
    </div>

    <!-- ── Notes ───────────────────────────────────────────────────────────── -->
    <div class="col-12">
        <label class="form-label" for="notes">
            Notes <span class="text-muted">(optional)</span>
        </label>
        <textarea id="notes" name="notes"
                  class="form-control" rows="3"
                  placeholder="Account details, renewal steps, GoDaddy reference…"><?= e($val('notes')) ?></textarea>
    </div>

</div>

<script>
(function () {
    // Show/hide custom billing days field
    var radios  = document.querySelectorAll('.billing-interval-radio');
    var wrapper = document.getElementById('billing-days-wrapper');
    var daysInput = document.getElementById('billing_days');

    function toggleDays() {
        var checked = document.querySelector('.billing-interval-radio:checked');
        var show    = checked && checked.value === 'custom';
        wrapper.style.display = show ? '' : 'none';
        if (daysInput) daysInput.required = show;
    }

    radios.forEach(function (r) { r.addEventListener('change', toggleDays); });
    toggleDays();
}());
</script>

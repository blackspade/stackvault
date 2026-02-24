<?php
/**
 * Shared credential create / edit form.
 *
 * Vars injected by the caller:
 *   $credential  array  — current values (for edit) or old-input (for create)
 *   $types       array  — CredentialModel::TYPES  [key => label]
 *   $clients     array  — ClientModel::getForSelect()
 *   $servers     array  — ServerModel::getForSelect()
 *   $domains     array  — DomainModel::getForSelect()
 *   $action      string — form action URL
 *   $submitLabel string — submit button text
 *   $isEdit      bool   — true when editing (changes password hint text)
 */
$v      = $credential ?? [];
$isEdit = $isEdit ?? false;
$sel    = fn(string $k, mixed $compare): string
    => (string) ($v[$k] ?? '') === (string) $compare ? 'selected' : '';
$val    = fn(string $k, mixed $def = ''): mixed => $v[$k] ?? $def;
?>
<form method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- ── Label ─────────────────────────────────────────────────────── -->
        <div class="col-md-8">
            <label class="form-label required">Label</label>
            <input type="text" name="label" class="form-control"
                   value="<?= e($val('label')) ?>"
                   placeholder="e.g. Hetzner Root SSH, Client cPanel, Cloudflare API Key"
                   required maxlength="255" autofocus>
            <div class="form-text">A short, recognisable name for this credential.</div>
        </div>

        <!-- ── Type ──────────────────────────────────────────────────────── -->
        <div class="col-md-4">
            <label class="form-label required">Type</label>
            <select name="credential_type" class="form-select">
                <?php foreach ($types as $typeKey => $typeLabel): ?>
                <option value="<?= e($typeKey) ?>" <?= $sel('credential_type', $typeKey) ?>>
                    <?= e($typeLabel) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ── Username ──────────────────────────────────────────────────── -->
        <div class="col-md-5">
            <label class="form-label">Username / Login</label>
            <input type="text" name="username" class="form-control"
                   value="<?= e($val('username')) ?>"
                   placeholder="root, admin, user@example.com…"
                   autocomplete="off">
        </div>

        <!-- ── Port ──────────────────────────────────────────────────────── -->
        <div class="col-md-2">
            <label class="form-label">Port</label>
            <input type="number" name="port" class="form-control"
                   value="<?= e($val('port')) ?>"
                   placeholder="22" min="1" max="65535">
        </div>

        <!-- ── Password ──────────────────────────────────────────────────── -->
        <div class="col-md-5">
            <label class="form-label">
                Password
                <?php if ($isEdit): ?>
                <span class="text-muted fw-normal small">(leave blank to keep current)</span>
                <?php endif; ?>
            </label>
            <div class="input-group">
                <input type="password" name="password" id="inp-password" class="form-control font-monospace"
                       placeholder="<?= $isEdit ? 'Leave blank to keep current' : 'Enter password…' ?>"
                       autocomplete="new-password">
                <button type="button" class="btn btn-outline-secondary" tabindex="-1"
                        onclick="svToggleVis('inp-password', this)" title="Show / hide">
                    <i class="ti ti-eye"></i>
                </button>
            </div>
        </div>

        <!-- ── Client ────────────────────────────────────────────────────── -->
        <div class="col-md-4">
            <label class="form-label">Client</label>
            <select name="client_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $sel('client_id', $c['id']) ?>>
                    <?= e($c['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ── Server ────────────────────────────────────────────────────── -->
        <div class="col-md-4">
            <label class="form-label">Server</label>
            <select name="server_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($servers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $sel('server_id', $s['id']) ?>>
                    <?= e($s['label']) ?><?= $s['ip_address'] ? ' (' . e($s['ip_address']) . ')' : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ── Domain ────────────────────────────────────────────────────── -->
        <div class="col-md-4">
            <label class="form-label">Domain</label>
            <select name="domain_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($domains as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $sel('domain_id', $d['id']) ?>>
                    <?= e($d['root_domain']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ── TOTP Secret (collapsible) ─────────────────────────────────── -->
        <div class="col-12">
            <?php $totpHasValue = $isEdit && !empty($v['totp_secret_encrypted']); ?>
            <a href="#" class="d-inline-flex align-items-center gap-1 text-muted small text-decoration-none"
               data-bs-toggle="collapse" data-bs-target="#totp-section" role="button"
               aria-expanded="<?= $totpHasValue ? 'true' : 'false' ?>">
                <i class="ti ti-shield-lock"></i>
                TOTP / 2FA Secret
                <i class="ti ti-chevron-down ms-1" id="totp-chevron"></i>
            </a>
            <div class="collapse<?= $totpHasValue ? ' show' : '' ?>" id="totp-section">
                <div class="mt-2 col-md-6">
                    <label class="form-label">
                        TOTP Secret
                        <?php if ($isEdit): ?>
                        <span class="text-muted fw-normal small">(leave blank to keep current)</span>
                        <?php endif; ?>
                    </label>
                    <div class="input-group">
                        <input type="password" name="totp_secret" id="inp-totp" class="form-control font-monospace"
                               placeholder="<?= $isEdit ? 'Leave blank to keep current' : 'Base32 TOTP secret…' ?>"
                               autocomplete="off">
                        <button type="button" class="btn btn-outline-secondary" tabindex="-1"
                                onclick="svToggleVis('inp-totp', this)" title="Show / hide">
                            <i class="ti ti-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Base32 key for authenticator apps (Google Authenticator, Authy, etc.).</div>
                </div>
            </div>
        </div>

        <!-- ── Notes ─────────────────────────────────────────────────────── -->
        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3"
                      placeholder="Additional context: sudo access, IP restrictions, API endpoint URL…"><?= e($val('notes')) ?></textarea>
        </div>

    </div><!-- /.row -->

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i><?= e($submitLabel) ?>
        </button>
        <a href="<?= url('/credentials') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<script>
function svToggleVis(inputId, btn) {
    const inp  = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type      = 'text';
        icon.className = 'ti ti-eye-off';
    } else {
        inp.type      = 'password';
        icon.className = 'ti ti-eye';
    }
}
// Rotate chevron on TOTP collapse toggle
document.querySelector('[data-bs-target="#totp-section"]')
    ?.addEventListener('click', function () {
        const ch = document.getElementById('totp-chevron');
        if (!ch) return;
        const expanded = this.getAttribute('aria-expanded') === 'true';
        ch.style.transform = expanded ? 'rotate(0deg)' : 'rotate(-180deg)';
    });
</script>

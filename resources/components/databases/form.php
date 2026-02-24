<?php
/**
 * Shared database create / edit form.
 *
 * Vars:
 *   $db          array  — current field values
 *   $types       array  — DatabaseModel::TYPES
 *   $clients     array  — ClientModel::getForSelect()
 *   $servers     array  — ServerModel::getForSelect()
 *   $apps        array  — ApplicationModel::getForSelect()
 *   $action      string — form POST URL
 *   $submitLabel string — button label
 *   $isEdit      bool   — true when editing
 */
$v      = $db ?? [];
$isEdit = $isEdit ?? false;
$sel    = fn(string $k, mixed $cmp): string
    => (string) ($v[$k] ?? '') === (string) $cmp ? 'selected' : '';
$val    = fn(string $k, mixed $def = ''): mixed => $v[$k] ?? $def;
?>
<form method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>

    <div class="row g-3">

        <!-- ── Type ──────────────────────────────────────────────────────── -->
        <div class="col-md-3">
            <label class="form-label required">Type</label>
            <select name="db_type" id="inp-db-type" class="form-select"
                    onchange="svDbTypeChanged(this.value)">
                <?php foreach ($types as $typeKey => $typeLabel): ?>
                <option value="<?= e($typeKey) ?>" <?= $sel('db_type', $typeKey) ?>>
                    <?= e($typeLabel) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ── Database Name ─────────────────────────────────────────────── -->
        <div class="col-md-5">
            <label class="form-label required">Database Name</label>
            <input type="text" name="db_name" class="form-control font-monospace"
                   value="<?= e($val('db_name')) ?>"
                   placeholder="myapp_production"
                   required maxlength="255" autofocus>
        </div>

        <!-- ── Host ──────────────────────────────────────────────────────── -->
        <div class="col-md-4">
            <label class="form-label required">Host</label>
            <input type="text" name="host" class="form-control font-monospace"
                   value="<?= e($val('host', 'localhost')) ?>"
                   placeholder="localhost or 127.0.0.1"
                   required maxlength="255">
        </div>

        <!-- ── Port ──────────────────────────────────────────────────────── -->
        <div class="col-md-2">
            <label class="form-label">Port</label>
            <input type="number" name="port" id="inp-port" class="form-control font-monospace"
                   value="<?= e($val('port', '3306')) ?>"
                   placeholder="3306" min="1" max="65535">
        </div>

        <!-- ── Username ──────────────────────────────────────────────────── -->
        <div class="col-md-5">
            <label class="form-label">Username</label>
            <input type="text" name="username" class="form-control font-monospace"
                   value="<?= e($val('username')) ?>"
                   placeholder="db_user" autocomplete="off">
        </div>

        <!-- ── Password ──────────────────────────────────────────────────── -->
        <div class="col-md-5">
            <label class="form-label">
                Password
                <?php if ($isEdit): ?>
                <span class="text-muted fw-normal small">(leave blank to keep current)</span>
                <?php endif; ?>
            </label>
            <?php if (!vault_unlocked()): ?>
            <div class="input-group">
                <input type="password" class="form-control" disabled
                       placeholder="Unlock vault to set password">
                <span class="input-group-text">
                    <a href="<?= url('/vault/unlock') ?>" class="text-warning">
                        <i class="ti ti-lock me-1"></i>Unlock
                    </a>
                </span>
            </div>
            <?php else: ?>
            <div class="input-group">
                <input type="password" name="password" id="inp-db-password" class="form-control font-monospace"
                       placeholder="<?= $isEdit ? 'Leave blank to keep current' : 'Enter password…' ?>"
                       autocomplete="new-password">
                <button type="button" class="btn btn-outline-secondary" tabindex="-1"
                        onclick="svToggleVis('inp-db-password', this)" title="Show / hide">
                    <i class="ti ti-eye"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- ── Client (searchable) ───────────────────────────────────────── -->
        <div class="col-md-4">
            <label class="form-label">Client</label>
            <div class="sv-select">
                <input type="hidden" name="client_id"
                       value="<?= (int)($v['client_id'] ?? 0) > 0 ? (int)$v['client_id'] : '' ?>">
                <input type="text" class="form-control sv-select-input"
                       autocomplete="off" placeholder="— None —">
                <div class="sv-select-dropdown">
                    <div class="sv-select-option" data-id="">— None —</div>
                    <?php foreach ($clients as $c): ?>
                    <div class="sv-select-option" data-id="<?= (int)$c['id'] ?>">
                        <?= e($c['name']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
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

        <!-- ── Application ───────────────────────────────────────────────── -->
        <div class="col-md-4">
            <label class="form-label">Application</label>
            <select name="app_id" class="form-select">
                <option value="">— None —</option>
                <?php foreach ($apps as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $sel('app_id', $a['id']) ?>>
                    <?= e($a['app_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- ── Notes ─────────────────────────────────────────────────────── -->
        <div class="col-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3"
                      placeholder="Charset, collation, backup schedule, special access notes…"><?= e($val('notes')) ?></textarea>
        </div>

    </div><!-- /.row -->

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i><?= e($submitLabel) ?>
        </button>
        <a href="<?= url('/databases') ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>

</form>

<script>
const SV_DEFAULT_PORTS = {
    mysql: 3306, mariadb: 3306, postgresql: 5432, sqlite: '', mssql: 1433, other: ''
};

function svDbTypeChanged(type) {
    const portEl = document.getElementById('inp-port');
    if (!portEl) return;
    const port = SV_DEFAULT_PORTS[type];
    if (port !== undefined) portEl.value = port;
}

function svToggleVis(inputId, btn) {
    const inp  = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type       = 'text';
        icon.className = 'ti ti-eye-off';
    } else {
        inp.type       = 'password';
        icon.className = 'ti ti-eye';
    }
}
</script>

<?php
/**
 * Vars: $credential[], $types[], $activity[]
 */
$cred       = $credential;
$id         = (int) $cred['id'];
$typeLabel  = $types[$cred['credential_type']] ?? $cred['credential_type'];
$badgeClass = \App\Models\CredentialModel::typeBadgeClass($cred['credential_type']);

// ── Page actions ──────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/credentials/' . $id . '/edit') ?>" class="btn btn-outline-secondary">
    <i class="ti ti-pencil me-1"></i>Edit
</a>
<button type="button" class="btn btn-outline-danger"
        onclick="document.getElementById('delete-form').classList.toggle('d-none')">
    <i class="ti ti-trash me-1"></i>Delete
</button>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Delete confirm form (hidden) ──────────────────────────────────────── -->
<div id="delete-form" class="card border-danger mb-4 d-none">
    <div class="card-body py-2 d-flex align-items-center gap-3">
        <span class="text-danger fw-medium">
            <i class="ti ti-alert-triangle me-1"></i>Permanently delete this credential?
        </span>
        <form method="post" action="<?= url('/credentials/' . $id . '/delete') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger">Yes, delete</button>
        </form>
        <button type="button" class="btn btn-sm btn-ghost-secondary"
                onclick="document.getElementById('delete-form').classList.add('d-none')">
            Cancel
        </button>
    </div>
</div>

<!-- ── Vault locked notice ────────────────────────────────────────────────── -->
<?php if (!vault_unlocked()): ?>
<div class="alert alert-warning alert-dismissible mb-4" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i class="ti ti-lock fs-4 flex-shrink-0"></i>
        <div>
            <strong>Vault is locked</strong> — passwords are hidden.
            <a href="<?= url('/vault/unlock') ?>" class="alert-link ms-1">Unlock Vault</a> to reveal them.
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Tabs ───────────────────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a href="#tab-overview" class="nav-link active" data-bs-toggle="tab" role="tab">
            <i class="ti ti-key me-1"></i>Overview
        </a>
    </li>
    <li class="nav-item">
        <a href="#tab-activity" class="nav-link" data-bs-toggle="tab" role="tab">
            <i class="ti ti-history me-1"></i>Activity
            <?php if (!empty($activity)): ?>
            <span class="badge bg-blue text-white ms-1"><?= count($activity) ?></span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<div class="tab-content">

<!-- ── Overview tab ──────────────────────────────────────────────────────── -->
<div class="tab-pane active show" id="tab-overview" role="tabpanel">
    <div class="row g-4">

        <!-- Left col: details -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title d-flex align-items-center gap-2">
                        <span class="badge <?= $badgeClass ?> fs-6"><?= e($typeLabel) ?></span>
                        <?= e($cred['label']) ?>
                    </h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">

                        <?php if ($cred['username']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Username</dt>
                        <dd class="col-sm-8 font-monospace">
                            <?= e($cred['username']) ?>
                            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 ms-1" title="Copy username"
                                    onclick="svCopyText(<?= json_encode($cred['username']) ?>, this)">
                                <i class="ti ti-clipboard"></i>
                            </button>
                        </dd>
                        <?php endif; ?>

                        <?php if ($cred['port']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Port</dt>
                        <dd class="col-sm-8 font-monospace"><?= (int) $cred['port'] ?></dd>
                        <?php endif; ?>

                        <?php if ($cred['client_name']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Client</dt>
                        <dd class="col-sm-8">
                            <a href="<?= url('/clients/' . $cred['client_id']) ?>">
                                <?= e($cred['client_name']) ?>
                            </a>
                        </dd>
                        <?php endif; ?>

                        <?php if ($cred['server_label']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Server</dt>
                        <dd class="col-sm-8">
                            <a href="<?= url('/servers/' . $cred['server_id']) ?>">
                                <?= e($cred['server_label']) ?>
                            </a>
                        </dd>
                        <?php endif; ?>

                        <?php if ($cred['domain_name']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Domain</dt>
                        <dd class="col-sm-8">
                            <a href="<?= url('/domains/' . $cred['domain_id']) ?>">
                                <?= e($cred['domain_name']) ?>
                            </a>
                        </dd>
                        <?php endif; ?>

                        <dt class="col-sm-4 text-muted fw-normal">Added</dt>
                        <dd class="col-sm-8 text-muted small"><?= e($cred['created_at']) ?></dd>

                        <?php if ($cred['last_viewed_at']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Last viewed</dt>
                        <dd class="col-sm-8 text-muted small">
                            <?= time_ago($cred['last_viewed_at']) ?>
                            <?= $cred['last_viewed_by_username']
                                ? ' by ' . e($cred['last_viewed_by_username']) : '' ?>
                        </dd>
                        <?php endif; ?>

                    </dl>
                </div>
            </div>

            <?php if ($cred['notes']): ?>
            <div class="card mt-3">
                <div class="card-header"><h4 class="card-title">Notes</h4></div>
                <div class="card-body">
                    <p class="text-muted mb-0" style="white-space:pre-wrap"><?= e($cred['notes']) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div><!-- /left col -->

        <!-- Right col: secrets -->
        <div class="col-lg-7">

            <!-- Password -->
            <div class="card mb-3">
                <div class="card-header">
                    <h4 class="card-title"><i class="ti ti-lock me-1"></i>Password</h4>
                </div>
                <div class="card-body">
                    <?php if (!$cred['password_encrypted']): ?>
                    <p class="text-muted mb-0"><em>No password stored.</em></p>
                    <?php elseif (!vault_unlocked()): ?>
                    <div class="text-muted d-flex align-items-center gap-2">
                        <span class="font-monospace fs-5 letter-spacing-wide">••••••••••••</span>
                        <a href="<?= url('/vault/unlock') ?>" class="btn btn-sm btn-outline-warning ms-2">
                            <i class="ti ti-lock me-1"></i>Unlock to reveal
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="input-group">
                        <input type="text" id="field-password" class="form-control font-monospace"
                               value="••••••••••••" readonly
                               style="letter-spacing:.2em; cursor:default">
                        <button type="button" class="btn btn-outline-secondary" id="btn-reveal-password"
                                data-revealed="0"
                                onclick="svRevealField(<?= $id ?>, 'password', this)">
                            <i class="ti ti-eye me-1"></i>Reveal
                        </button>
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="svCopyField('field-password', this)" title="Copy password">
                            <i class="ti ti-copy me-1"></i>Copy
                        </button>
                    </div>
                    <div class="form-text">Password auto-hides 60 seconds after reveal.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TOTP Secret -->
            <?php if ($cred['totp_secret_encrypted']): ?>
            <div class="card mb-3">
                <div class="card-header">
                    <h4 class="card-title"><i class="ti ti-shield-lock me-1"></i>TOTP Secret</h4>
                </div>
                <div class="card-body">
                    <?php if (!vault_unlocked()): ?>
                    <div class="text-muted d-flex align-items-center gap-2">
                        <span class="font-monospace fs-5">••••••••••••</span>
                        <a href="<?= url('/vault/unlock') ?>" class="btn btn-sm btn-outline-warning ms-2">
                            <i class="ti ti-lock me-1"></i>Unlock to reveal
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="input-group">
                        <input type="text" id="field-totp" class="form-control font-monospace"
                               value="••••••••••••" readonly
                               style="letter-spacing:.2em; cursor:default">
                        <button type="button" class="btn btn-outline-secondary" id="btn-reveal-totp"
                                data-revealed="0"
                                onclick="svRevealField(<?= $id ?>, 'totp', this)">
                            <i class="ti ti-eye me-1"></i>Reveal
                        </button>
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="svCopyField('field-totp', this)" title="Copy TOTP secret">
                            <i class="ti ti-copy me-1"></i>Copy
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /right col -->
    </div><!-- /.row -->
</div><!-- /#tab-overview -->

<!-- ── Activity tab ──────────────────────────────────────────────────────── -->
<div class="tab-pane" id="tab-activity" role="tabpanel">
    <?php if (empty($activity)): ?>
    <div class="text-center text-muted py-5">
        <i class="ti ti-history fs-1 d-block mb-2 opacity-50"></i>
        No activity recorded yet.
    </div>
    <?php else: ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Description</th>
                        <th>User</th>
                        <th>IP</th>
                        <th>When</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($activity as $log): ?>
                <?php
                    $actIcon  = match (true) {
                        str_contains($log['action'], 'created')  => 'ti-plus text-success',
                        str_contains($log['action'], 'updated')  => 'ti-pencil text-blue',
                        str_contains($log['action'], 'deleted')  => 'ti-trash text-danger',
                        str_contains($log['action'], 'revealed') => 'ti-eye text-warning',
                        default                                  => 'ti-activity text-muted',
                    };
                ?>
                <tr>
                    <td>
                        <span class="d-flex align-items-center gap-1">
                            <i class="ti <?= $actIcon ?>"></i>
                            <span class="text-muted small"><?= e($log['action']) ?></span>
                        </span>
                    </td>
                    <td class="text-muted"><?= e($log['description']) ?></td>
                    <td class="text-muted small"><?= e($log['username'] ?? '—') ?></td>
                    <td class="text-muted small font-monospace"><?= e($log['ip_address'] ?? '—') ?></td>
                    <td class="text-muted small"><?= time_ago($log['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div><!-- /#tab-activity -->

</div><!-- /.tab-content -->

<?php if (vault_unlocked()): ?>
<script>
const SV_CSRF        = <?= json_encode(csrf_token()) ?>;
const SV_REVEAL_URL  = <?= json_encode(url('/credentials/' . $id . '/reveal')) ?>;
let   svAutoHideTimer = null;

function svRevealField(credId, field, btn) {
    const inputEl   = document.getElementById('field-' + field);
    const isRevealed = btn.dataset.revealed === '1';

    if (isRevealed) {
        // Hide
        inputEl.value         = '••••••••••••';
        inputEl.style.letterSpacing = '.2em';
        btn.dataset.revealed  = '0';
        btn.innerHTML         = '<i class="ti ti-eye me-1"></i>Reveal';
        if (svAutoHideTimer) { clearTimeout(svAutoHideTimer); svAutoHideTimer = null; }
        return;
    }

    const orig  = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Loading…';

    fetch(SV_REVEAL_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_token=' + encodeURIComponent(SV_CSRF) + '&field=' + encodeURIComponent(field)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.error) {
            btn.innerHTML = orig;
            alert(data.error);
            return;
        }
        inputEl.value               = data.value || '(empty)';
        inputEl.style.letterSpacing = 'normal';
        btn.dataset.revealed        = '1';
        btn.innerHTML               = '<i class="ti ti-eye-off me-1"></i>Hide';

        // Auto-hide after 60 s
        if (svAutoHideTimer) clearTimeout(svAutoHideTimer);
        svAutoHideTimer = setTimeout(() => svRevealField(credId, field, btn), 60000);
    })
    .catch(() => {
        btn.disabled  = false;
        btn.innerHTML = orig;
    });
}

function svCopyField(inputId, btn) {
    const inputEl = document.getElementById(inputId);
    const val     = inputEl.value;
    if (!val || val.startsWith('•')) return;
    svCopyText(val, btn);
}

function svCopyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check me-1"></i>Copied!';
        btn.classList.add('btn-success');
        btn.classList.remove('btn-outline-secondary', 'btn-ghost-secondary');
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-secondary');
        }, 2000);
    });
}
</script>
<?php else: ?>
<script>
function svCopyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="ti ti-check"></i>';
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    });
}
</script>
<?php endif; ?>

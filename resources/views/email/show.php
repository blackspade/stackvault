<?php
/**
 * Vars: $account[], $activity[]
 */
$id = (int) $account['id'];
?>

<?php
// ── Page actions ──────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/email/' . $id . '/edit') ?>" class="btn btn-outline-secondary">
    <i class="ti ti-pencil me-1"></i>Edit
</a>
<button type="button" class="btn btn-outline-danger"
        onclick="document.getElementById('delete-form').classList.toggle('d-none')">
    <i class="ti ti-trash me-1"></i>Delete
</button>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<!-- ── Delete confirm ─────────────────────────────────────────────────────── -->
<div id="delete-form" class="card border-danger mb-4 d-none">
    <div class="card-body py-2 d-flex align-items-center gap-3">
        <span class="text-danger fw-medium">
            <i class="ti ti-alert-triangle me-1"></i>Permanently delete this email account?
        </span>
        <form method="post" action="<?= url('/email/' . $id . '/delete') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-sm btn-danger">Yes, delete</button>
        </form>
        <button type="button" class="btn btn-sm btn-ghost-secondary"
                onclick="document.getElementById('delete-form').classList.add('d-none')">
            Cancel
        </button>
    </div>
</div>

<!-- ── Tabs ───────────────────────────────────────────────────────────────── -->
<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item">
        <a href="#tab-overview" class="nav-link active" data-bs-toggle="tab" role="tab">
            <i class="ti ti-mail me-1"></i>Overview
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

<!-- ── Overview ──────────────────────────────────────────────────────────── -->
<div class="tab-pane active show" id="tab-overview" role="tabpanel">
    <div class="row g-4">

        <!-- Left col: details -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-blue-lt text-blue">
                        <i class="ti ti-mail"></i>
                    </span>
                    <h3 class="card-title mb-0"><?= e($account['email_address']) ?></h3>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">

                        <?php if ($account['mail_host']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Mail Host</dt>
                        <dd class="col-sm-8 font-monospace">
                            <?= e($account['mail_host']) ?>
                            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 ms-1"
                                    onclick="svCopyText(<?= json_encode($account['mail_host']) ?>, this)"
                                    title="Copy hostname">
                                <i class="ti ti-clipboard"></i>
                            </button>
                        </dd>
                        <?php endif; ?>

                        <dt class="col-sm-4 text-muted fw-normal">SMTP Port</dt>
                        <dd class="col-sm-8 font-monospace"><?= (int) $account['smtp_port'] ?></dd>

                        <dt class="col-sm-4 text-muted fw-normal">IMAP Port</dt>
                        <dd class="col-sm-8 font-monospace"><?= (int) $account['imap_port'] ?></dd>

                        <?php if ($account['username']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Username</dt>
                        <dd class="col-sm-8 font-monospace">
                            <?= e($account['username']) ?>
                            <button type="button" class="btn btn-sm btn-ghost-secondary p-0 ms-1"
                                    onclick="svCopyText(<?= json_encode($account['username']) ?>, this)"
                                    title="Copy username">
                                <i class="ti ti-clipboard"></i>
                            </button>
                        </dd>
                        <?php endif; ?>

                        <?php if ($account['webmail_url']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Webmail</dt>
                        <dd class="col-sm-8">
                            <a href="<?= e($account['webmail_url']) ?>" target="_blank" rel="noopener">
                                <i class="ti ti-external-link me-1"></i>Open Webmail
                            </a>
                        </dd>
                        <?php endif; ?>

                        <?php if ($account['client_name']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Client</dt>
                        <dd class="col-sm-8">
                            <a href="<?= url('/clients/' . $account['client_id']) ?>">
                                <?= e($account['client_name']) ?>
                            </a>
                        </dd>
                        <?php endif; ?>

                        <?php if ($account['root_domain']): ?>
                        <dt class="col-sm-4 text-muted fw-normal">Domain</dt>
                        <dd class="col-sm-8">
                            <a href="<?= url('/domains/' . $account['domain_id']) ?>">
                                <?= e($account['root_domain']) ?>
                            </a>
                        </dd>
                        <?php endif; ?>

                        <dt class="col-sm-4 text-muted fw-normal">Added</dt>
                        <dd class="col-sm-8 text-muted small"><?= e($account['created_at']) ?></dd>

                    </dl>
                </div>
            </div>

            <?php if ($account['notes']): ?>
            <div class="card mt-3">
                <div class="card-header"><h4 class="card-title">Notes</h4></div>
                <div class="card-body">
                    <p class="text-muted mb-0" style="white-space:pre-wrap"><?= e($account['notes']) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right col: password + server config -->
        <div class="col-lg-7">

            <!-- Password reveal -->
            <div class="card mb-3">
                <div class="card-header">
                    <h4 class="card-title"><i class="ti ti-lock me-1"></i>Password</h4>
                </div>
                <div class="card-body">
                    <?php if (!$account['password_encrypted']): ?>
                    <p class="text-muted mb-0"><em>No password stored.</em></p>
                    <?php elseif (!vault_unlocked()): ?>
                    <div class="d-flex align-items-center gap-2">
                        <span class="font-monospace fs-5 letter-spacing-wide text-muted">••••••••••••</span>
                        <a href="<?= url('/vault/unlock') ?>" class="btn btn-sm btn-outline-warning ms-2">
                            <i class="ti ti-lock me-1"></i>Unlock to reveal
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="input-group">
                        <input type="text" id="field-password" class="form-control font-monospace"
                               value="••••••••••••" readonly style="letter-spacing:.2em; cursor:default">
                        <button type="button" class="btn btn-outline-secondary" id="btn-reveal-password"
                                data-revealed="0"
                                onclick="svRevealPassword(<?= $id ?>, this)">
                            <i class="ti ti-eye me-1"></i>Reveal
                        </button>
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="svCopyField('field-password', this)">
                            <i class="ti ti-copy me-1"></i>Copy
                        </button>
                    </div>
                    <div class="form-text">Password auto-hides after 60 seconds.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Server config quick-reference -->
            <?php if ($account['mail_host']): ?>
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><i class="ti ti-settings me-1"></i>Server Configuration</h4>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless mb-0">
                        <tbody>
                            <tr>
                                <td class="text-muted ps-3" style="width:130px">SMTP Server</td>
                                <td class="font-monospace"><?= e($account['mail_host']) ?>:<?= (int)$account['smtp_port'] ?></td>
                                <td class="pe-3 text-end">
                                    <button type="button" class="btn btn-sm btn-ghost-secondary"
                                            onclick="svCopyText('<?= e($account['mail_host']) ?>:<?= (int)$account['smtp_port'] ?>', this)"
                                            title="Copy SMTP">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted ps-3">IMAP Server</td>
                                <td class="font-monospace"><?= e($account['mail_host']) ?>:<?= (int)$account['imap_port'] ?></td>
                                <td class="pe-3 text-end">
                                    <button type="button" class="btn btn-sm btn-ghost-secondary"
                                            onclick="svCopyText('<?= e($account['mail_host']) ?>:<?= (int)$account['imap_port'] ?>', this)"
                                            title="Copy IMAP">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php if ($account['username']): ?>
                            <tr>
                                <td class="text-muted ps-3">Username</td>
                                <td class="font-monospace"><?= e($account['username']) ?></td>
                                <td class="pe-3 text-end">
                                    <button type="button" class="btn btn-sm btn-ghost-secondary"
                                            onclick="svCopyText(<?= json_encode($account['username']) ?>, this)"
                                            title="Copy username">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /right col -->
    </div><!-- /.row -->
</div><!-- /#tab-overview -->

<!-- ── Activity ───────────────────────────────────────────────────────────── -->
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
                    $icon = match (true) {
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
                            <i class="ti <?= $icon ?>"></i>
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
</div>

</div><!-- /.tab-content -->

<?php if (vault_unlocked()): ?>
<script>
const SV_CSRF       = <?= json_encode(csrf_token()) ?>;
const SV_REVEAL_URL = <?= json_encode(url('/email/' . $id . '/reveal')) ?>;
let   svAutoHide    = null;

function svRevealPassword(emailId, btn) {
    const inputEl    = document.getElementById('field-password');
    const isRevealed = btn.dataset.revealed === '1';

    if (isRevealed) {
        inputEl.value               = '••••••••••••';
        inputEl.style.letterSpacing = '.2em';
        btn.dataset.revealed        = '0';
        btn.innerHTML               = '<i class="ti ti-eye me-1"></i>Reveal';
        if (svAutoHide) { clearTimeout(svAutoHide); svAutoHide = null; }
        return;
    }

    const orig    = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading…';

    fetch(SV_REVEAL_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_token=' + encodeURIComponent(SV_CSRF)
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.error) { btn.innerHTML = orig; alert(data.error); return; }
        inputEl.value               = data.value || '(empty)';
        inputEl.style.letterSpacing = 'normal';
        btn.dataset.revealed        = '1';
        btn.innerHTML               = '<i class="ti ti-eye-off me-1"></i>Hide';
        if (svAutoHide) clearTimeout(svAutoHide);
        svAutoHide = setTimeout(() => svRevealPassword(emailId, btn), 60000);
    })
    .catch(() => { btn.disabled = false; btn.innerHTML = orig; });
}

function svCopyField(inputId, btn) {
    const val = document.getElementById(inputId)?.value;
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
        btn.innerHTML = '<i class="ti ti-check me-1"></i>Copied!';
        setTimeout(() => { btn.innerHTML = orig; }, 1500);
    });
}
function svCopyField(inputId, btn) {
    const val = document.getElementById(inputId)?.value;
    if (!val) return;
    svCopyText(val, btn);
}
</script>
<?php endif; ?>

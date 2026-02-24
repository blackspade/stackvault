<?php
/**
 * Vars: $credentials[], $clients[], $types[], $search, $filterType, $filterClient
 */

// ── Page actions ──────────────────────────────────────────────────────────────
ob_start(); ?>
<a href="<?= url('/credentials/create') ?>" class="btn btn-primary d-none d-sm-inline-flex">
    <i class="ti ti-plus me-1"></i>Add Credential
</a>
<?php \App\Core\View::share('pageActions', ob_get_clean()); ?>

<?php if (!vault_unlocked()): ?>
<div class="alert alert-warning alert-dismissible mb-4" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i class="ti ti-lock fs-4 flex-shrink-0"></i>
        <div>
            <strong>Vault is locked</strong> — passwords cannot be copied or revealed.
            <a href="<?= url('/vault/unlock') ?>" class="alert-link ms-1">Unlock Vault</a>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- ── Filter bar ──────────────────────────────────────────────────────────── -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="get" action="<?= url('/credentials') ?>" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control"
                       placeholder="Search label or username…"
                       value="<?= e($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">All Types</option>
                    <?php foreach ($types as $typeKey => $typeLabel): ?>
                    <option value="<?= e($typeKey) ?>" <?= $filterType === $typeKey ? 'selected' : '' ?>>
                        <?= e($typeLabel) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="client_id" class="form-select">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterClient === (int) $c['id'] ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="ti ti-search me-1"></i>Filter
                </button>
                <?php if ($search || $filterType || $filterClient): ?>
                <a href="<?= url('/credentials') ?>" class="btn btn-outline-secondary" title="Clear filters">
                    <i class="ti ti-x"></i>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- ── Credentials table ──────────────────────────────────────────────────── -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?= count($credentials) ?> credential<?= count($credentials) !== 1 ? 's' : '' ?>
            <?php if ($search || $filterType || $filterClient): ?>
            <span class="badge bg-blue-lt text-blue ms-2">filtered</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($credentials)): ?>
    <div class="card-body text-center text-muted py-5">
        <i class="ti ti-key-off fs-1 d-block mb-2 opacity-50"></i>
        <?php if ($search || $filterType || $filterClient): ?>
            No credentials match your filters.
        <?php else: ?>
            No credentials yet.
            <a href="<?= url('/credentials/create') ?>">Add your first credential.</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th style="width:110px">Type</th>
                    <th>Label</th>
                    <th>Username</th>
                    <th>Linked To</th>
                    <th style="width:140px">Last Viewed</th>
                    <th style="width:100px"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($credentials as $cred): ?>
            <?php
                $badgeClass = \App\Models\CredentialModel::typeBadgeClass($cred['credential_type']);
                $typeLabel  = $types[$cred['credential_type']] ?? $cred['credential_type'];

                // Determine linked-to display
                $linkedParts = [];
                if ($cred['client_name'])  $linkedParts[] = '<i class="ti ti-building me-1 text-muted"></i>' . e($cred['client_name']);
                if ($cred['server_label']) $linkedParts[] = '<i class="ti ti-server me-1 text-muted"></i>'   . e($cred['server_label']);
                if ($cred['domain_name'])  $linkedParts[] = '<i class="ti ti-world me-1 text-muted"></i>'    . e($cred['domain_name']);
            ?>
            <tr>
                <td>
                    <span class="badge <?= $badgeClass ?>">
                        <?= e($typeLabel) ?>
                    </span>
                </td>
                <td>
                    <a href="<?= url('/credentials/' . $cred['id']) ?>" class="fw-medium text-reset text-decoration-none">
                        <?= e($cred['label']) ?>
                    </a>
                </td>
                <td class="text-muted">
                    <?= $cred['username'] ? e($cred['username']) : '<span class="text-muted">—</span>' ?>
                    <?= $cred['port'] ? '<span class="text-muted small">:' . e($cred['port']) . '</span>' : '' ?>
                </td>
                <td class="text-muted small">
                    <?= $linkedParts ? implode('<br>', $linkedParts) : '—' ?>
                </td>
                <td class="text-muted small">
                    <?= $cred['last_viewed_at'] ? time_ago($cred['last_viewed_at']) : '<span class="text-muted">Never</span>' ?>
                </td>
                <td class="text-end">
                    <div class="btn-group btn-group-sm">
                        <a href="<?= url('/credentials/' . $cred['id']) ?>"
                           class="btn btn-ghost-secondary" title="View">
                            <i class="ti ti-eye"></i>
                        </a>
                        <a href="<?= url('/credentials/' . $cred['id'] . '/edit') ?>"
                           class="btn btn-ghost-secondary" title="Edit">
                            <i class="ti ti-pencil"></i>
                        </a>
                        <button type="button"
                                class="btn btn-ghost-secondary"
                                title="<?= vault_unlocked() ? 'Copy password' : 'Unlock vault to copy password' ?>"
                                onclick="svCopyPassword(<?= (int) $cred['id'] ?>, this)">
                            <i class="ti ti-clipboard"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
const SV_CSRF          = <?= json_encode(csrf_token()) ?>;
const SV_VAULT_OPEN    = <?= json_encode(vault_unlocked()) ?>;
const SV_UNLOCK_URL    = <?= json_encode(url('/vault/unlock')) ?>;
const SV_REVEAL_BASE   = <?= json_encode(url('/credentials/')) ?>;

function svCopyPassword(credId, btn) {
    if (!SV_VAULT_OPEN) {
        window.location.href = SV_UNLOCK_URL;
        return;
    }

    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';

    fetch(SV_REVEAL_BASE + credId + '/reveal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: '_token=' + encodeURIComponent(SV_CSRF) + '&field=password'
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.error) {
            btn.innerHTML = orig;
            alert(data.error);
            return;
        }
        if (!data.value) {
            btn.innerHTML = '<i class="ti ti-ban"></i>';
            setTimeout(() => { btn.innerHTML = orig; }, 1500);
            return;
        }
        navigator.clipboard.writeText(data.value).then(() => {
            btn.innerHTML = '<i class="ti ti-check"></i>';
            btn.classList.add('text-success');
            setTimeout(() => {
                btn.innerHTML = orig;
                btn.classList.remove('text-success');
            }, 2000);
        }).catch(() => {
            btn.innerHTML = orig;
        });
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = orig;
    });
}
</script>
